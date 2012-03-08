###
Create all necessary defaults and model groups for the collections
###
Formloader = _.defaults(Formloader or {}, 
  Uri : do ->
    path = "#{window.location.origin}#{window.location.pathname}"
    path = path.replace("<?=Uri::base()?>", '')
    if path.substr(-1) is '/'
      path = path.substr(0, (path.length - 1))
    path = path.split('/')
  UrlBase : "<?=Uri::base()?>formloader"
  Collections : {}
  Models : {}
  View   : {}
  Views  : {}
  Vars   : {}
  Templates : {}
  Proxy     : {}
  Helpers   : {}
  Forms     : {} # Used for individual fields that need data, like the option k => v
  FieldTypes : {}
)

_.extend Formloader.Proxy, Backbone.Events

Formloader.Templates.Alert = _.template("""
<div class="alert alert-<%= type %>">
  <a class="close" data-dismiss="alert">×</a>
  <%= message %>
</div>
""")
Formloader.Templates.Modal = _.template("""
<div class="modal">
  <div class="modal-header">
    <a class="close" data-dismiss="modal">×</a>
    <h3><%= title %></h3>
  </div>
  <div class="modal-body">
    <%= form %>
  </div>
</div>
""")
Formloader.Templates.Validation = _.template("""
<form>
  <div class="clearfix" data-formloader-wrapper="email">
  	<% if(prompt){ %>
  	<label for="subVal"><%= prompt %></label>
  	<div class="input">
  		 <input id="subVal" name="subVal" value="<%= subVal %>" type="text">
  	</div>
  	<% }else{ %>
  	<label for="subVal"><strong>Info:</strong></label>
  	<div class="input">
      <p><%= clicktip %></p>
	  </div>
	  <% } %>
  </div>
  <div class="form-actions">
    <button id="saveModal" class="btn btn-info" type="submit"><% if(prompt){ %>Submit<% }else{ %>Save<% } %></button>
  </div>
</form>
""")
Formloader.Templates.HoverTrash = _.template("""
<a href="#" class="icon-trash" style="display:none" data-formloader-hidefield="<%= name %>"></a>
""")
Formloader.Templates.Options = _.template("""
<div class="optionRow">
   <div>
      <input type="text" class="inline span2" name="<%= name %>[key][]" placeholder="key" value="<%= key %>" />
      <input type="text" class="inline span2" name="<%= name %>[value][]" placeholder="value" value="<%= value %>" />
      <a href="#" data-formloader-action="<%= type %>" class="btn <%= _class %>"><%= sign %></a>
      <span class="btn small disabled optionSortHandle">Sort</span>
   </div>
   <br>
</div>
""")

# Empty all of the inputs associated with the dropdown...
Formloader.Helpers.emptyAllInputs = ($domElement) ->
  $domElement.find(':input').each () ->
    Formloader.Proxy.trigger("emptied",
      name : $(@).attr('name')
    );
    $empty = $(@).val('')
    if @.tagName.toLowerCase() is "select"
      $empty.change()

# Used to determine if this is a single empty input field...
Formloader.Helpers.isEmptyInput = ($domElement) ->
  inputs = $domElement.find(':input')
  if inputs.length is 1
    if $(inputs[0]).attr('value') is ''
      true
  else
    # if there is at least one filled field, we keep the set - we can destroy that individually...
    for input in inputs
      if $(input).attr('value') isnt ''
        return false
    true
  
# This allows us to create a popup with the field we wish to be modifying while
# keeping the current scope open...
Formloader.Helpers.Popup = (path, opts, data) ->
  windowName    = opts?.windowName or '_blank'
  windowOptions = opts?.windowOptions or 'location=0,status=0,scrollbars=yes,width=940,height=800'
  callback      = opts?.callback or -> window.location.reload()

  @_oauthWindow   = window.open(path, windowName, windowOptions);
  @_oauthInterval = window.setInterval( =>
    if @_oauthWindow.closed
      window.clearInterval @_oauthInterval
      callback()
  , 1000)
  if (data?)
    @_oauthWindow.document.write(data)

Formloader.Model = Backbone.Model.extend
  idAttribute: '_id'
  defaults :
    group  : ''       # group of the item
    name   : ''       # name of the item
    label  : ''       # group.name if we're not in the current group, name otherwise
    subVal : ''       # sub-selection of the validation
    bound    : false  # whether this is used elsewhere (can't use an item in both a fieldset and field)
    selected : false  # if it's selected or not in the current context
    prompt   : false  # prompt used to get the details for validation
    clicktip : false  # tip used for the validation (if not a prompt)
  initialize : () ->
    if @collection.type is "validations"
      @.on "change:selected", (m) ->
        if @.get('selected') is false
          @.set('subVal', '')
      , @
  
Formloader.Collection = Backbone.Collection.extend
  url   : () ->
    "#{Formloader.UrlBase}/js/#{@type}"
  model : Formloader.Model
  initialize: (m, opts) ->
    @type = opts.type
    @url  = @url()
    if opts.type is "fields"
      Formloader.Proxy.on('fieldset:switc', (group, label, switc) ->
        @.find((item) =>
          if item.get('group') is group and item.get('label') is label then item.set('bound', switc)
        )
      , @)
    null

  # only add items to the collection if the ID doesn't exist - 
  # so we are only adding new items on refresh...
  parse: (resp) ->
    _.filter resp, (item) =>
      not @get(item._id)
  
Formloader.View.AutoComplete = Backbone.View.extend
  events :
    'click [data-formloader-add]' : (e) ->
      e.preventDefault()
      if @collection.type isnt "validations"
        Formloader.Helpers.Popup "#{Formloader.UrlBase}/#{$(e.currentTarget).attr('data-formloader-add')}/create?ref=popup",
          callback : () =>
            @collection.fetch
              add: true

  initialize : ->
    @$tagit = @$el.find('ul');

    if @collection.type isnt "validations"
      @$('[data-formloader-add]').show()

    # Bind this object's functions to the current scope
    _.bind @tagToggle, @
    _.bind @validatePrompt, @
    _.bind @addStartTags, @

    # SubCalls are defined in the views/js/ folder
    if @.options.subCall then _.bind @.options.subCall, @
    
    @initStatus = false

    # Setup the main TagIt Interface
    @$tagit.tagit
      requireAutocomplete : do =>
        @collection.type isnt "validations"
      removeConfirmation  : true
      itemName  : 'tagit'
      fieldName : @$tagit.attr('data-tagit-name'),
      tagSource : (e, show) => 
        show(@collection.chain().filter((item) ->
          item.get("selected") isnt true and item.get("bound") isnt true and (item.get("label").toLowerCase().indexOf(e.term) is 0 or item.get("name").toLowerCase().indexOf(e.term) is 0)
        ).map((model) -> 
          if model.get("group") is Formloader.Vars.GroupName then model.get("name") else "#{model.get('name')}.#{model.get('group')}"
        ).value())
      onTagAdded: (e, tag) => 
        @tagToggle(tag, true)
        if @collection.type is "validations" and @initStatus is true
          @validatePrompt(tag, true)
        null
      onTagRemoved: (e, tag) =>
        @tagToggle(tag, false)
        null
      onTagClicked: (e, tag) =>
        if @collection.type isnt "validations"
          type = @collection.type
          _id = tag.attr('data-formloader_id')
          Formloader.Helpers.Popup "#{Formloader.UrlBase}/#{type}/#{_id}?ref=popup",
            callback : () =>
              @collection.fetch
                add: true
        else
          @validatePrompt(tag)
          e.preventDefault()

    # And make the tags jQ UI sortable
    .sortable()
    @

  addStartTags : (tags) ->
    for tag in tags
      if tag.indexOf('.') is -1 and Formloader.Vars.GroupName isnt ''
        item  = tag + '.' + Formloader.Vars.GroupName
      else
        item  = tag.split('.')
        item  = "#{item[1]}.#{item[0]}"
      @$tagit.tagit("createTag", ""+item)
    @initStatus = true

  validatePrompt: (tag, added, callback) ->
    tagId = tag.attr('data-formloader_id').split('[');
    model = @collection.get(tagId[0]) or false
    if model and (added isnt true or model.get('prompt') isnt false)
      ValidationView = Backbone.View.extend
        events : 
          "click #saveModal" : (e) ->
            e.preventDefault()
            @$el.modal('hide')
        render: (form) ->
          @$el.html Formloader.Templates.Modal
            form:form, 
            title:"Set Validation"
          @$el.modal('show')
          @$el.on 'hide', =>
            if (model.get('prompt') isnt false)
              subVal = @$el.find('#subVal').attr('value')
              if subVal? and subVal isnt ''
                model.set('subVal', subVal)
                tag.find('.tagit-label').text("#{model.get('name')}[#{subVal}]")
                tag.attr('data-formloader_id', "#{model.get('_id')}[#{subVal}]")
                null
              else
                model.set('selected', false)
                tag.remove()
                false
          @$el.on 'hidden', =>
            @.remove();
      new ValidationView().render(
        Formloader.Templates.Validation(model.toJSON())
      )
  
  tagToggle : (tag, set) ->
    subVal = -1
    tagVal = tag.find('input').attr('value');
    if tagVal.indexOf(".") is -1
      tagVal = "#{Formloader.Vars.GroupName}.#{tagVal}"
    else
      # superimpose the tag val, so that we can find/bind it...
      tagVal = tagVal.split('.')
      subVal = tagVal[0].search(/\[(.*?)\]/)
      if subVal isnt -1
        subVal = tagVal[0].match(/\[(.*?)\]/)[1]
      tagVal = "#{tagVal[1]}.#{tagVal[0].replace(/\[(.*?)\]/, '')}"
      
    # find the model by the group.name combination
    model = @collection.find (item) ->
      item.get('label') is tagVal
    if model
      @model = model
      if subVal isnt -1 
        model.set('subVal', subVal)
        tag.find('.tagit-label').text("#{model.get('name')}[#{subVal}]")
        tag.attr('data-formloader_id', "#{model.get('_id')}[#{subVal}]")
      else
        if set and @model.get('group') is Formloader.Vars.GroupName or @model.get('group') is "fuel"
          tag.find('.tagit-label').text(@model.get('name'))
        tag.attr('data-formloader_id', @model.get('_id'))
      @model.set('selected', set)
      if @.options.subCall
        @.options.subCall(tag, @model)
    else
      if @collection.type is "validations"
        tag.removeClass('ui-state-default')
        tag.addClass('ui-button-primary')
      else
        tag.addClass('ui-state-error')
    null

# An entire grouping of collapsible fields...
Formloader.FieldTypes.CollapsibleFieldset = Backbone.View.extend
  events :
    "click [data-formloader-showfieldtrigger]" : (e) ->
      e.preventDefault()
      $e = $(e.target)
      if $e.attr('data-formloader-showfieldtrigger') is "show"
        $e.attr('data-formloader-showfieldtrigger', "hide")
        $e.text('Hide Empty Fields')
        @collection.each (m) ->
          m.set('visible', true)
      else
        $e.text('Show All Fields')
        $e.attr('data-formloader-showfieldtrigger', "show")
        @collection.each (m) ->
          if Formloader.Helpers.isEmptyInput(m.get('target')) is true
            m.set('visible', false)
    "click [data-formloader-showfield]" : (e) ->
      e.preventDefault()
      m = @collection.get($(e.target).attr('data-formloader-showfield'))
      if m?
        m.set('visible', true)
        if not @collection.find((item) -> item.get('visible') is false)?
          @$('[data-formloader-showfieldtrigger]').click()
        @delegateEvents()
    "click a[data-formloader-hidefield]" : (e) ->
      e.preventDefault()
      m = @collection.get($(e.currentTarget).attr('data-formloader-hidefield'))
      if m?
        m.set('visible', false)
        Formloader.Helpers.emptyAllInputs(m.get('target'))
        @delegateEvents()
    "hover label" : (e) ->
      if e.type is "mouseenter"
        $(e.target).find('a').show()
      else
        $(e.target).find('a').hide()

  initialize : () ->
    @collection = new @collection()
    @$("[data-formloader-wrapper]").each (k, i) =>
      $i = $(i)
      id = $i.attr('data-formloader-wrapper')
      @collection.add
        id  : id
        link : @$("[data-formloader-showfield=\"#{id}\"]")
        target : $i
        visible : _.indexOf(Formloader.unhide, id) isnt -1
      $i.find('label').prepend(Formloader.Templates.HoverTrash(name:id));

## NEW SELECT FUNCTIONS #
Formloader.FieldTypes.OptionRow = Backbone.View.extend
  events:
    "click [data-formloader-action]" : (e) ->
      e.preventDefault()
      switch  $(e.target).attr('data-formloader-action')
        when "add_option_row"
          $(e.target)
          .removeClass("btn-primary")
          .addClass("btn-danger")
          .attr('data-formloader-action', 'remove_option_row')
          .text('-')
          @addOptionRow()
        when "remove_option_row"
          $(e.target).closest('div.optionRow').remove();

      # Make sure that it's still sortable after doing this or that...
      @$el.sortable
        handle : @$('span')

  initialize: () ->
    _.bind @addOptionRow, @

    options = Formloader.Forms[@$el.attr('data-option-sgroup')] or false
    if options
      for key, val of options
        @addOptionRow(key, val)

    # make the items sortable on init...
    @$el.sortable
      handle : @$('span')

  addOptionRow: (key, val) ->
    tmpl = Formloader.Templates.Options
      name   : @$el.attr('data-option-sgroup')
      key    : key
      value  : val
      type   : (if key? then 'remove_option_row' else 'add_option_row')
      sign   : (if key? then '-' else '+')
      _class : (if key? then 'btn-danger' else 'btn-primary')
    $last = @$('div.optionRow').last()
    if key?
       $last.before(tmpl)
    else
       $last.after(tmpl)
    
    Formloader.Proxy.on "emptied", (e) =>
      if e.name.indexOf("#{@$el.attr("data-option-sgroup")}") isnt -1
        @$('div.optionRow:not(:last-child)').remove()
    
  #addSelectRow = () ->

  #addCheckboxRow = (key, val, checked) ->
    

# jQuery doc.ready action!
$ ->

  # Allows us to know what the group name is...
  Formloader.Vars.Group = $("#group")
  Formloader.Vars.GroupName = Formloader.Vars.Group.attr('value')
  Formloader.Vars.Group.on 'keyup', (e) ->
    # This indent matters...
    Formloader.Vars.GroupName = $(@).attr('value');
  
  # PHP Generated output block...
  `
    <?= isset($use) ? 'Formloader.Vars.Use = '.$use : '' ?>
    <?= isset($init) ? $init : '' ?>
  `
  
  # Didn't want to mess around with the jQ tag-it and making/validating
  # a serious number of verbose backbone objects to deal with it for right now
  # just serialize it, throw it in the form, and submit it... maybe clean this up for 2.0
  toggleAppend = () ->
    $("li.tagit-choice").each () ->
      $input = $(this).find('input')
      id = $(this).attr('data-formloader_id')
      inputVal = $input.attr('value')
      $input.attr('value', id)
      $(this).attr('data-formloader_id', inputVal)
      null

  # Deal with each option group populated by javascript the multi k => v one...
  $("[data-option-sgroup]").each () ->
    new Formloader.FieldTypes.OptionRow
      el : @

  # Deal with each collapsible fieldset individually...
  $("[data-collapsible-fieldset]").each () ->
    new Formloader.FieldTypes.CollapsibleFieldset
      el : @
      collection : Backbone.Collection.extend
        model : Backbone.Model.extend
          defaults: 
            target : ''     # dom element to toggle
            link   : ''     # dom link to toggle
            visible : false
          initialize : () ->
            if @.get('visible') is true
              @showItem(@)
            @on 'change:visible', (m) ->
              if m.get('visible') is true
                @showItem(m)
              else
                @hideItem(m)
            , @
          hideItem : (m, init) ->
            m.get('link').show()
            m.get('target').attr('value', '').hide()            
          showItem : (m, init) ->
            m.get('target').show()
            m.get('link').hide()
            m.get('target').find('a[data-formloader-hidefield]').hide()

  ## Bind the options change to check if it's a dropdown, 
  ## in which case we show the buttons...
  checkValue = ($item) ->
    if _.indexOf(['dropdown', 'checkboxes', 'radios'], $item?.attr('value')) isnt -1
      $("[data-formloader-optsettings]").show()
    else
      $("[data-formloader-optsettings]").hide()

  ## Check the fieldtype to determine if we need to change what's shown
  $("[data-formloader-fieldtype]").on "change", (e) ->
    checkValue($(this))

  ## ...and then check at the init
  checkValue($("[data-formloader-fieldtype]"))

  ## bind to the button click of the actions group (compile/save/preview)
  $("[data-formloader-actions]").on "click", "button", (e) ->

    type = $(e.target).attr('value')

    $form = $(this).closest('form')
    $form.append('<div id="formloader_appended_output"/>')
    $("#formloader_appended_output").append("<input id='formloader_appended_submit' type='hidden' name='submit' value='#{type}' />")
    
    toggleAppend()
    
    formloader_alerts = (data) ->
      data = $.parseJSON(data)
      $("#formloader_appended_output").remove()
      toggleAppend()
      $form.prepend Formloader.Templates.Alert
        type : data.type
        message : data.message
      window.scrollTo(0, 50)
      setTimeout () ->
        $(".alert").fadeOut()
      , 5000
    
    _.each Formloader.Views, (view) =>
      if _.indexOf(Formloader.Vars.Use, view.collection.type) isnt -1
        # item is a collection
        view.collection.each (item) ->
          if item.get('selected') is true and item.get('bound') is false
            # Do this to ensure that our json in the form is sanitized of single quotes, 
            # otherwise it fails silently on the processing end
            stringSave = "#{JSON.stringify(item)}".replace(/\'/, "&#39;");
            $("#formloader_appended_output").append("<input type='hidden' name='tagit_#{view.collection.type}[]' value='#{stringSave}' />")
      null

    if type is "Preview"
      $.post("#{Formloader.UrlBase}/api/preview/#{Formloader.Uri[1]}", $form.serialize())
      .success((data) ->
        PreviewView = Backbone.View.extend
          events :
            "click" : (e) ->
              if $(e.target).is('button') or $(e.target).is('submit')
                e.preventDefault()
          render: (form) ->
            @$el.html Formloader.Templates.Modal
              form: form,
              title: "Preview"
            @$el.modal('show')
            @$el.on 'hidden', =>
              @.remove();
            toggleAppend()
        new PreviewView().render(data)
        $("#formloader_appended_output").remove()
      )
      .error(formloader_alerts)
  null