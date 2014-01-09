/**
 * Copyright (c) 2013 Art & Logic, Inc. All Rights Reserved.
 *
 * Handles the user interface, login/registration forms, popups, etc.
 */
function AUi(options){
    this.options = $.extend({
        debug:true
    }, (options || {}));
    this.baseURL = $('link').first().attr('href').substr(0,
        $('link').first().attr('href').indexOf('static'));

    this.init();
    this.listen();
}

/**
 * Initialize any related plugins and values.
 */
AUi.prototype.init = function(){
    // init coverflow
    $('.coverflow').coverflow();
    // init selectpickers
    $('.intent').selectpicker({
        'style':'btn-mega',
        'width':'180px'
    });
    $('.verb, .interests').selectpicker({
        'style':'btn-mega'
    });
};

/**
 * Listen for events on related elements.
 */
AUi.prototype.listen = function(){
    var that = this;
    // 'go' button for verb selection on main view
    $('#actionGo').on('click touchstart', function(event){
        event.preventDefault();
        var verb = parseInt($('#selectverb').val());
        switch(verb)
        {
            case 0: // Accept Invite
                that.setActiveSections('#regwrap, #actionwrap');
                break;
            case 1: // Sign In
                $('#actionwrap').slideUp();
                $('#signinwrap').slideDown();
                break;
            case 2: // Countdown page
                break;
            case 3: // go home
                that.setActiveSections('#coverwrap, #actionwrap');
                break;
        }
    });

    // registration form submit button - prevent default for submit if form valid,
    // send form contents to server
    $('#regform > button[type="submit"]').on('click', function(event){
        if ($(this).parent()[0].checkValidity())
        {
            event.preventDefault();
            var reginvite = $('#reg-invitecode');
            if (reginvite.val() == "")
            {
                reginvite.val(reginvite.attr('data-invitecode'));
            }
            if ($('#reg-pass').val() === $('#reg-pass-confirm').val())
            {
                that.submitReg($(this).parent());
            }
            else
            {
                $('#reg-pass-confirm').after(
                    '<p id="passMatchError"><small>Your passwords must match.</small></p>'
                );
                $('#reg-pass, #reg-pass-confirm').css('background-color', 'red').on('focus', function(){
                    $(this).css('background-color', 'transparent');
                    $('#passMatchError').remove();
                });
            }
        }
    });

    // Login button, cancel button and 'remember you checkbox' button
    $('#signinRemButton').on('click touchstart', function(event){
        event.preventDefault();
        $(this).toggleClass('select');
        if ($(this).hasClass('select'))
        {
            $(this).find('i').removeClass('icon-stop').addClass('icon-white icon-check');
        }
        else
        {
            $(this).find('i').removeClass('icon-white icon-check').addClass('icon-stop');
        }
    }).tooltip();
    $('#siginLogButton').on('click touchstart', function(event){
        if ($(this).parent()[0].checkValidity())
        {
            event.preventDefault();
            that.login();
        }
    });
    $('#signinCancelButton').on('click touchstart', function(event){
        event.preventDefault();
        $('#actionwrap').slideDown();
        $('#signinwrap').slideUp();
    });
};

/**
 * Set the active section(s).
 * @param sections
 */
AUi.prototype.setActiveSections = function(sections){
    $('.section.active').removeClass('active').hide();
    $('.section').filter(sections).addClass('active').slideDown();
};

/**
 * Submit member registration.
 * @param form
 */
AUi.prototype.submitReg = function(form)
{
    var that = this,
        vals = {};
    form.serializeArray().map(function(ob){
        vals[ob.name] = ob.value;
    });
    vals['interests'] = $('.selectpicker.interests').val();

    // Disable register button and show progress
    $('#regform > button[type="submit"]').text('Please Wait...').prop('disabled', 'disabled');
    $('#progressModal').modal({
        backdrop:'static',
        keyboard:false
    });

    $.ajax({
        data:{member:JSON.stringify(vals)},
        datatype:'json',
        type:'POST',
        url:that.baseURL+"index.php/home/storeMember"
    }).done(function(res)
    {
        $('#progressModal').modal('hide');
        $('#regform > button[type="submit"]').text('Register').prop('disabled', false);
        if (!res.success)
        {
            (!res.memberID) ? that.options.debug && console.log('Failed to add member to table.') :
                that.options.debug && console.log("Failed to send confirmation email.");
            return;
        }
        that.options.debug && console.log(res);
        $('.first_name').text(vals['first_name']);
        that.setActiveSections('#regFinish, #actionwrap');
    }).fail(function(jqXHR)
    {
        $('#progressModal').modal('hide');
        that.options.debug && console.log(jqXHR.statusText);
    });
}

/**
 * Perform login and, if successful, display countdown view.
 */
AUi.prototype.login = function(){
    var that = this,
        email = $('#signin-email').val(),
        password = $('#signin-password').val();

    $.ajax({
        data:{'email_address':email, 'password':password},
        dataType:'json',
        type:'POST',
        url:that.baseURL+"index.php/home/login"
    }).done(function(res)
    {
        if (res.success)
        {
            $('#countdown .first_name').text(res['first_name']);

            that.setActiveSections('#countdownwrap, #actionwrap');
            return;
        }
        that.options.debug && console.log(res);
    }).fail(function(jqXHR)
    {
        that.options.debug && console.log(jqXHR.statusText);
    })
};