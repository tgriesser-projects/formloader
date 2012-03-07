###
 * validation in coffee
 * @author Tim Griesser
 * @license MIT
 * Based on validate.js by Rick Harrison
 * http://rickharrison.github.com/validate.js
###
"use strict"
###
If you would like an application-wide config, change these defaults.
Otherwise, use the setMessage() function to configure form specific messages.
###
defaults =
  messages:
    required: 'The %s field is required.'
    matches: 'The %s field does not match the %s field.'
    valid_email: 'The %s field must contain a valid email address.'
    valid_emails: 'The %s field must contain all valid email addresses.'
    min_length: 'The %s field must be at least %s characters in length.'
    max_length: 'The %s field must not exceed %s characters in length.'
    exact_length: 'The %s field must be exactly %s characters in length.'
    greater_than: 'The %s field must contain a number greater than %s.'
    less_than: 'The %s field must contain a number less than %s.'
    alpha: 'The %s field must only contain alphabetical characters.'
    alpha_numeric: 'The %s field must only contain alpha-numeric characters.'
    alpha_dash: 'The %s field must only contain alpha-numeric characters, underscores, and dashes.'
    numeric: 'The %s field must contain only numbers.'
    integer: 'The %s field must contain an integer.'
    decimal: 'The %s field must contain a decimal number.'
    is_natural: 'The %s field must contain only positive numbers.'
    is_natural_no_zero: 'The %s field must contain a number greater than zero.'
    valid_ip: 'The %s field must contain a valid IP.'
    valid_base64: 'The %s field must contain a base64 string.'
  callback: (errors) ->
    alert(errors)

ruleRegex = /^(.+)\[(.+)\]$/
numericRegex = /^[0-9]+$/
integerRegex = /^\-?[0-9]+$/
decimalRegex = /^\-?[0-9]*\.?[0-9]+$/
emailRegex = /^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,6}$/i
alphaRegex = /^[a-z]+$/i
alphaNumericRegex = /^[a-z0-9]+$/i
alphaDashRegex = /^[a-z0-9_\-]+$/i
naturalRegex = /^[0-9]+$/i
naturalNoZeroRegex = /^[1-9][0-9]*$/i
ipRegex = /^((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})$/i
base64Regex = /[^a-zA-Z0-9\/\+=]/i

###
The exposed public object to validate a form:
  @param formName - String - The name attribute of the form (i.e. <form name="myForm"></form>)
  @param fields - Array or {
    name: The name of the element (i.e. <input name="myField" />)
    display: 'Field Name'
    rules: required|matches[password_confirm]
 }
  @param callback - Function - The callback after validation has been performed.
  @argument errors - An array of validation errors
  @argument event - The javascript event
 ###
class FormValidator 

  constructor: (formName, fields, callback) ->
    @callback = callback ? defaults.callback
    @errors = []
    @fields = {}
    @form   = document.forms[formName] or {}
    @messages = {}
    @handlers = {}
    
    for field in fields
      temp = {}
      if field instanceof Array and field.length is 3
        temp.name    = field[0];
        temp.display = field[1];
        temp.rules   = field[2];
        field = temp;
    
      # Silently discard errors, so if passed in incorrectly, we can still generate the form
      if field.name and field.rules
        # Build the master fields array that has all the information needed to validate
        @fields[field.name] =
          name: field.name
          display: field.display || field.name
          rules: field.rules
          type: null
          value: null
          checked: null


    # Attach an event callback for the form submission
    @form.onsubmit = do =>
	    return (event) =>
		    try
		      @._validateForm(event)
		    catch error

  setMessage : (rule, message) ->
      @messages[rule] = message;
      @;

  registerCallback: (name, handler) ->
    if (name? and handler?)
      if typeof name is 'string' and typeof handler is 'function'
        @.handlers[name] = handler;
    return @;
  
  # Validating a form...
  _validateForm: (event) ->
    @errors = [];
    for own key, value of @fields
      field = @fields[key] || {}
      element = @form[field.name]
      if element?
        field.type = element.type
        field.value = element.value
        field.checked = element.checked
      @_validateField(field, field.name)
    
    if typeof @callback is 'function'
      @callback(@errors, event);
    
    if @errors.length > 0
        if event?
	        event.preventDefault()
        else
          return false
    true

  # Validates an individual field by field name, based on the given rules
  _validateField: (field, fieldName) ->
      rules = field.rules.split('|');

      # If the value is null and not required, we don't need to run through validation
      if field.rules.indexOf('required') is -1 and not field.value?
          return;

      # Run through the rules and execute the validation rules as needed
      for rule in rules 
        param  = null
        failed = false
        result = {}

        # If the rule has a parameter (i.e. matches[param]) split it out
        parts = ruleRegex.exec(rule);

        if (parts)
          rule = parts[1]
          param = parts[2]

        # If the hook is defined, run it to find any validation errors
        if (typeof @_hooks[rule] is 'function')
          if ( ! @_hooks[rule].apply(this, [field, param]))
            failed = true;
        else if (rule.substring(0, 9) is 'callback_')
          # Custom rule. Execute the handler if it was registered
          rule = rule.substring(9, rule.length);
          if (typeof @handlers[rule] is 'function')
            if (@handlers[rule].apply(this, [field.value]) is false)
              failed = true;

        # If the hook failed, add a message to the errors array
        if (failed)
          # Make sure we have a message for this rule
          source = if @messages[rule] then @messages[rule] else defaults.messages[rule];
          result = {'name' : fieldName};
          if (source?)
            result.message = source.replace('%s', field.display)
            if (param?)
              result.message = result.message.replace('%s', if @fields[param] then @fields[param].display else param)
          else
            result.message = 'An error has occurred with the ' + field.display + ' field.';
          
          @errors.push(result);
          break; # Break out so as to not spam with validation errors (i.e. required and valid_email)

  _hooks :
    required: (field) ->
      if (field.type is 'checkbox')
        field.checked is true
      else
        return field?.value and field.value != ''
    matches: (field, matchName) -> field.value is @form[matchName].value
    valid_email: (field) -> emailRegex.test(field.value)
    valid_emails: (field) ->
      emails = field.value.split(",")
      for email in emails
        emailRegex.test(email)
    min_length: (field, length) -> numericRegex.test(length) and field.value.length >= length
    max_length: (field, length) -> numericRegex.test(length) and field.value.length <= length
    exact_length: (field, length) -> numericRegex.test(length) and field.value.length is parseInt(length, 10)
    greater_than: (field, param) ->
      if ! decimalRegex.test(field.value)
        false
      parseFloat(field.value) > parseFloat(param)
    less_than: (field, param) ->
      if ( ! decimalRegex.test(field.value))
        false
      parseFloat(field.value) < parseFloat(param)
    alpha: (field) -> alphaRegex.test(field.value)
    alpha_numeric: (field) -> alphaNumericRegex.test(field.value)
    alpha_dash: (field) -> alphaDashRegex.test(field.value)
    numeric: (field) -> decimalRegex.test(field.value)
    integer: (field) -> integerRegex.test(field.value)
    decimal: (field) -> decimalRegex.test(field.value)
    is_natural: (field) -> naturalRegex.test(field.value)
    is_natural_no_zero: (field) -> naturalNoZeroRegex.test(field.value)
    valid_ip: (field) -> ipRegex.test(field.value)
    valid_base64: (field) -> base64Regex.test(field.value)

window.FormValidator = FormValidator;