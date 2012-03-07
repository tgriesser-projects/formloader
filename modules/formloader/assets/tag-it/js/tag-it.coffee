###
jQuery UI Tag-it!

@version v2.0 (06/2011)

Copyright 2011, Levy Carneiro Jr.
Released under the MIT license.
http://aehlke.github.com/tag-it/LICENSE

Homepage:
  http://aehlke.github.com/tag-it/

Authors:
  Levy Carneiro Jr.
  Martin Rehfeld
  Tobias Schmidt
  Skylar Challand
  Alex Ehlke

Maintainer:
  Alex Ehlke - Twitter: @aehlke

Dependencies:
  jQuery v1.4+
  jQuery UI v1.8+
###
$ = jQuery

$.widget 'ui.tagit'
  options:
    itemName          : 'item'
    fieldName         : 'tags'
    availableTags     : []
    tagSource         : null
    removeConfirmation: false
    caseSensitive     : true

    # When enabled, quotes are not neccesary
    # for inputting multi-word tags.
    allowSpaces: false

    # Tag delimiters to use in addition to space, enter and tab
    delimiterKeyCodes: [$.ui.keyCode.COMMA]

    # Whether to animate tag removals or not.
    animate: true

    # The below options are for using a single field instead of several
    # for our form values.
    #
    # When enabled, will use a single hidden field for the form,
    # rather than one per tag. It will delimit tags in the field
    # with singleFieldDelimiter.
    #
    # The easiest way to use singleField is to just instantiate tag-it
    # on an INPUT element, in which case singleField is automatically
    # set to true, and singleFieldNode is set to that element. This 
    # way, you don't need to fiddle with these options.
    singleField: false
    singleFieldDelimiter: ','

    # Set this to an input DOM node to use an existing form field.
    # Any text in it will be erased on init. But it will be
    # populated with the text of tags as they are created,
    # delimited by singleFieldDelimiter.
    #
    # If this is not set, we create an input node for it,
    # with the name given in settings.fieldName, 
    # ignoring settings.itemName.
    singleFieldNode: null

    # Optionally set a tabindex attribute on the input that gets
    # created for tag-it.
    tabIndex: null

    # Whether to only create tags only from autocomplete suggestions
    requireAutocomplete: false

    # Display title attribute as hint.
    hints: true

    # Hint animation.
    hintHideEffect: 'fade'
    hintHideEffectOptions: {}
    hintHideEffectSpeed: 200

    # Whether to remove the selected tag and all the tags that were added after it when deleting a tag.
    pruneTags: false

    # Event callbacks.
    onTagAdded  : null
    onTagRemoved: null
    onTagClicked: null

    onAutocompleteSelected: null

  _create: () -> 
      # for handling static scoping inside callbacks
      var that = this;

      # There are 2 kinds of DOM nodes this widget can be instantiated on:
      #     1. UL, OL, or some element containing either of these.
      #     2. INPUT, in which case 'singleField' is overridden to true,
      #        a UL is created and the INPUT is hidden.
      if @element.is('input')
          @tagList = $('<ul></ul>').insertAfter(@element);
          @options.singleField = true;
          @options.singleFieldNode = @element;
          @element.css('display', 'none');
      } else {
          @tagList = @element.find('ul, ol').andSelf().last();
      }

      @_tagInput = $('<input type="text" />').addClass('ui-widget-content');
      @_hintOverlay = $('<li></li>').addClass('tagit-hint ui-widget-content').text(@element.attr('title')||"");

      if (@options.tabIndex) {
          @_tagInput.attr('tabindex', @options.tabIndex);
      }

      if (!@options.tagSource && @options.availableTags.length > 0) {
          @options.tagSource = (search, showChoices) => 
              var filter = search.term.toLowerCase();
              var choices = $.grep(@options.availableTags, (element) -> 
                  # Only match autocomplete options that begin with the search term.
                  # (Case insensitive.)
                  return (element.toLowerCase().indexOf(filter) === 0);
              )
              showChoices(@_subtractArray(choices, @assignedTags()));
          };
      }

      # Bind tagSource callback functions to this context.
      if ($.isFunction(@options.tagSource)) {
          @options.tagSource = $.proxy(@options.tagSource, this);
      }

      # cannot require autocomplete without an autocomplete source
      if (!@options.tagSource) {
          @options.requireAutocomplete = false;
      }

      @tagList
          .addClass('tagit')
          .addClass('ui-widget ui-widget-content ui-corner-all')
          # Create the input field.
          .append($('<li class="tagit-new"></li>').append(@_tagInput))
          .click((e) => 
              var target = $(e.target);
              if (target.hasClass('tagit-label')) {
                  @_trigger('onTagClicked', e, target.closest('.tagit-choice'));
              } else {
                  # Sets the focus() to the input field, if the user
                  # clicks anywhere inside the UL. This is needed
                  # because the input field needs to be of a small size.
                  @_tagInput.focus();
              }
          )

      # Add existing tags from the list, if any.
      @tagList.children('li').each(() => 
          if (!$(this).hasClass('tagit-new')) {
              @createTag($(this).html(), $(this).attr('class'));
              $(this).remove();
          }
      )

      # Single field support.
      if (@options.singleField) {
          if (@options.singleFieldNode) {
              # Add existing tags from the input field.
              var node = $(@options.singleFieldNode);
              var tags = node.val().split(@options.singleFieldDelimiter);
              node.val('');
              $.each(tags, (index, tag) => 
                  @createTag(tag);
              )
          } else {
              # Create our single field input after our list.
              @options.singleFieldNode = @tagList.after('<input type="hidden" style="display:none;" value="" name="' + @options.fieldName + '" />');
          }
      }

      if (@options.allowSpaces !== true) {
          @options.delimiterKeyCodes.push($.ui.keyCode.SPACE);
      }

      if (@options.hints && @element.attr('title') !== undefined) {
          @tagList.prepend(@_hintOverlay);
      }
      if (@tagList.children('.tagit-choice').size() != 0) {
          @_hintOverlay.hide();
      }

      # Events.
      @_tagInput.keydown((event) => 
          # Backspace is not detected within a keypress, so it must use keydown.
          if (event.which == $.ui.keyCode.BACKSPACE && @_tagInput.val() === '') {
              var tag = @_lastTag();
              if (!@options.removeConfirmation || tag.hasClass('remove')) {
                  # When backspace is pressed, the last tag is deleted.
                  @removeTag(tag);
              } else if (@options.removeConfirmation) {
                  tag.addClass('remove ui-state-highlight');
              }
          } else if (@options.removeConfirmation) {
              @_lastTag().removeClass('remove ui-state-highlight');
          }

          if (@options.requireAutocomplete !== true) {
              # Any keyCode in options.delimiterKeyCodes, in addition to
              # Enter, are valid delimiters for new tags except when
              # there is an open quote.
              # Tab will also create a tag, unless the tag input is
              # empty, in which case it isn't caught.
              if (
                  event.which == $.ui.keyCode.ENTER ||
                  (
                      event.which == $.ui.keyCode.TAB &&
                      @_tagInput.val() !== ''
                  ) ||
                  (
                      ($.inArray(event.which, @options.delimiterKeyCodes) >= 0) &&
                      @_tagInputHasClosedQuotes()
                  )
              ) {
                  event.preventDefault();
                  @createTag(@_cleanedInput());

                  # The autocomplete doesn't close automatically when TAB is pressed.
                  # So let's ensure that it closes.
                  @_tagInput.autocomplete('close');
              }
          } else if (event.which == $.ui.keyCode.ENTER) {
              event.preventDefault();
          }
      )

      if (@options.requireAutocomplete !== true) {
          @_tagInput.blur((e) => 
              # Create a tag when the element loses focus (unless it's empty).
              @createTag(@_cleanedInput());
              if (@tagList.children('.tagit-choice').size() == 0) {
                  @_hintOverlay.show();
              }
          }).focus((e) => 
              @_hintOverlay.hide(
                  @options.hintHideEffect,
                  @options.hintHideEffectOptions,
                  @options.hintHideEffectSpeed);
          )
      }

      # Autocomplete.
      if (@options.tagSource) {
          @_tagInput.autocomplete({
              source: @options.tagSource,
              select: (event, ui) => 
                  # Delete the last tag if we autocomplete something despite the input being empty
                  # This happens because the input's blur event causes the tag to be created when
                  # the user clicks an autocomplete item.
                  # The only artifact of this is that while the user holds down the mouse button
                  # on the selected autocomplete item, a tag is shown with the pre-autocompleted text,
                  # and is changed to the autocompleted text upon mouseup.
                  if (@_tagInput.val() === '') {
                      @removeTag(@_lastTag(), false);
                  }
                  var tag = @createTag(ui.item.value);
                  # Preventing the tag input to be updated with the chosen value.
                  @_trigger('onAutocompleteSelected', event, {
                      item: ui.item,
                      tag: tag
                  )
                  return false;
              }
          )
      }
  
  _cleanedInput: () -> 
      # Returns the contents of the tag input, cleaned and ready to be passed to createTag
      return $.trim(@_tagInput.val().replace(/^"(.*)"$/, '$1'));
  
  _lastTag: () -> 
      return @tagList.children('.tagit-choice:last');
  
  _tagInputHasClosedQuotes: () -> 
      var inputVal = @_tagInput.val();
      return $.trim(inputVal).replace( /^s*/, '' ).charAt(0) != '"' ||
      (
          $.trim(inputVal).charAt(0) == '"' &&
          $.trim(inputVal).charAt($.trim(inputVal).length - 1) == '"' &&
          $.trim(inputVal).length - 1 !== 0
      )
  
  assignedTags: () -> 
      # Returns an array of tag string values
      that = this;
      tags = [];
      if (@options.singleField) {
          tags = $(@options.singleFieldNode).val().split(@options.singleFieldDelimiter);
          if (tags[0] === '') {
              tags = [];
          }
      } else {
          @tagList.children('.tagit-choice').each(() -> 
              tags.push(that.tagLabel(this));
          )
      }
      return tags;
  
  _updateSingleTagsField: (tags) -> 
      # Takes a list of tag string values, updates @options.singleFieldNode.val to the tags delimited by @options.singleFieldDelimiter
      $(@options.singleFieldNode).val(tags.join(@options.singleFieldDelimiter));
  
  _subtractArray: (a1, a2) -> 
      var result = [];
      for (var i = 0; i < a1.length; i++) {
          if ($.inArray(a1[i], a2) == -1) {
              result.push(a1[i]);
          }
      }
      return result;
  
  tagLabel: (tag) -> 
      # Returns the tag's string label.
      if (@options.singleField) {
          return $(tag).children('.tagit-label').text();
      } else {
          return $(tag).children('input').val();
      }
  
  _isNew: (value) -> 
      var that = this;
      var isNew = true;
      @tagList.children('.tagit-choice').each((i) -> 
          if (that._formatStr(value) == that._formatStr(that.tagLabel(this))) {
              isNew = false;
              return false;
          }
      )
      return isNew;
  
  _formatStr: (str) -> 
      if (@options.caseSensitive) {
          return str;
      }
      return $.trim(str.toLowerCase());
  
  createTag: (value, additionalClass) -> 
      var that = this;
      # Automatically trims the value of leading and trailing whitespace.
      value = $.trim(value);

      if (!@_isNew(value) || value === '') {
          return false;
      }

      var label = $(@options.onTagClicked ? '<a class="tagit-label"></a>' : '<span class="tagit-label"></span>').text(value);

      # Create tag.
      var tag = $('<li></li>')
          .addClass('tagit-choice ui-widget-content ui-state-default ui-corner-all')
          .addClass(additionalClass)
          .append(label);

      # Button for removing the tag.
      var removeTagIcon = $('<span></span>')
          .addClass('ui-icon ui-icon-close');
      var removeTag = $('<a><span class="text-icon">\xd7</span></a>') # \xd7 is an X
          .addClass('tagit-close')
          .append(removeTagIcon)
          .click((e) -> 
              # Removes a tag when the little 'x' is clicked.
              that.removeTag(tag);
          )
      tag.append(removeTag);

      # Unless options.singleField is set, each tag has a hidden input field inline.
      if (@options.singleField) {
          var tags = @assignedTags();
          tags.push(value);
          @_updateSingleTagsField(tags);
      } else {
          var escapedValue = label.html();
          tag.append('<input type="hidden" style="display:none;" value="' + escapedValue + '" name="' + @options.itemName + '[' + @options.fieldName + '][]" />');
      }

      @_trigger('onTagAdded', null, tag);

      # Cleaning the input.
      @_tagInput.val('');

      # Hide any hint text (possible if createTag is called externally)
      @_hintOverlay.hide();

      # insert tag
      return tag.insertBefore(@_tagInput.parent());
  
  removeTag: (tag, animate, removeOnly) -> 
    var that = this;

    if @options.pruneTags && !removeOnly
      that.pruneTag(tag)

    animate = animate || @options.animate;

    tag = $(tag);

    @_trigger('onTagRemoved', null, tag);

    if (@options.singleField)
        var tags = @assignedTags();
        var removedTagLabel = @tagLabel(tag);
        tags = $.grep(tags, function(el){
            return el != removedTagLabel;
        )
        @_updateSingleTagsField(tags);

    # Animate the removal.
    if animate
      tag.fadeOut('fast').hide('blind', {direction: 'horizontal'}, 'fast', function(){
          tag.remove();
      }).dequeue();
    else
      tag.remove();

    # Show any hint text
    tag.queue((next) -> 
      if (!that._tagInput.is(':focus') && that.tagList.children('.tagit-choice').size() == 0) {
          that._hintOverlay.show();
      next();

  removeAll: () -> 
    # Removes all tags.
    var that = this;
    @tagList.children('.tagit-choice').each((index, tag) -> 
      that.removeTag(tag, false);
    )
  
  pruneTag: (targetTag) -> 
      # Removes the specified tag and all the tags that were added after it.
      console.log('pruning')
      var that = this;
      targetTag = $(targetTag)[0];
      console.log(targetTag);

      var found = false;

      @tagList.children('.tagit-choice').each((index, tag) -> 
          if tag == targetTag
            found = true;
          if found
            that.removeTag(tag, {}, true);
      )
