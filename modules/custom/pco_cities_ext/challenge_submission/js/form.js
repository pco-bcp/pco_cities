(function ($) {
  $(document).ready(function () {

    //Add accepts (this is handled by Drupal, but Drupal tears off the accept. We will add it here.)
    setTimeout(function() {$('#edit-proposal-image-upload').children('input').attr('accept', '.jpg,.png')});
    setTimeout(function() {$('#edit-proposal-upload').children('input').attr('accept', '.docx,.pdf,.doc')});

    //Handlers for forms
    $('input, textarea, select').on('focusout', function () {
      $('.form-item').removeClass('group-error');
      $('.form-item').children('.group-error-description').remove();
      $('.alert-danger-list').hide();
      $('.error-list').html('');

      validateElement(this);
    });

    $('.btn-submit').on('click', function () {
      $('.form-item').removeClass('group-error');
      $('.form-item').children('.group-error-description').remove();
      $('.alert-danger-list').hide();
      $('.error-list').html('');

      var result = validateForm(this);

      if(!result) {
        return false;
      }
    });

    //Textarea word count
    $(".summary-word-count").html($('textarea').val().split(' ').length);

    //Textarea red text after maxlength
    $("textarea").keyup(function () {
      var maxlength = 150; // specify the maximum length
      var words = this.value.match(/\S+/g).length;

      $(".summary-word-count").html(words);

      if (words > maxlength) {
        $(".word-count").css('color', '#DD3030');
      } else {
        $(".word-count").css('color', 'black');
      }
    });

    //Ajax Submit Handler for Google Form
    $(".google-form").submit(function(e) {

      var url = $('.google-form').attr('action');

      $.ajax({
        type: "POST",
        url: url,
        data: $(".google-form").serialize(),
        complete: function()
        {
          $('#google-form-submit').removeClass('hidden');
          $('#google-feedback-form').hide();
        }
      });

      e.preventDefault();
    });
  });

  function validateElement(elementP) {
    var form = $("#test-form");
    form.validate();

    //Adds errors to input that are empty.
    if ($(elementP).val().length == 0 && $(elementP).attr('required') == 'required') {
      $(elementP).addClass('error'); //add check for required
    }

    //Selects all invalid fields and adds parent
    $('input.error, textarea.error').each(function () {
      var message = '<strong>' + $(this).attr('drupal-field-name') + '</strong> field is required.'
      $(this).closest('.form-item').addClass('group-error');
      $(this).closest('.form-item').append('<p class="group-error-description">' + message + '</p>');
    });

    //Add additional validation
    if ($('textarea').val().split(' ').length > 150) {
      if(!$('textarea').hasClass('error')) {
        $('textarea').addClass('error');
        var message = '<strong>' + $('textarea').attr('drupal-field-name') + '</strong> is over the word limit.'
        $('textarea').closest('.form-item').append('<p class="group-error-description">' + message + '</p>')
       }
    }

    //Check for valid email address
    if ($('#edit-primary-contact-email').val().length > 1 && !validateEmail($('#edit-primary-contact-email').val())) {
      if(!$('#edit-primary-contact-email').hasClass('error')) {
        $('#edit-primary-contact-email').addClass('error');
        $('#edit-primary-contact-email').closest('.form-item').addClass('group-error');
        var message = '<strong>' + $('#edit-primary-contact-email').attr('drupal-field-name') + '</strong> is not a valid email address.'
        $('#edit-primary-contact-email').closest('.form-item').append('<p class="group-error-description">' + message + '</p>')
      }
    }

    //Add messages to alert above. Show is error count is over 1
    if ($('.group-error-description').length > 0) {
      $('.alert-danger-list').show();
      $('.group-error-description').each(function () {
        $('.error-list').append('<li><a href="#' + $(this).parent().find('input, textarea').attr("id") + '">' + $(this).html() + '</a></li>');
      });
    }
  }

  function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }

  function validateForm() {
    var form = $("#test-form");
    var noErrors = true;
    form.validate();

    if ($('#edit-proposal-upload').val() == '') {
      $('#edit-proposal-upload').addClass('error');
      $('#edit-proposal-upload').closest('.form-item').addClass('group-error');
      $('#edit-proposal-upload').closest('.form-item').append('<p class="group-error-description mrgn-tp-lg">The <strong>Proposal</strong> field is required.</p>')
      noErrors = false;
    }

    if ($('#edit-proposal-image-upload').val() == '') {
      $('#edit-proposal-image-upload').addClass('error');
      $('#edit-proposal-image-upload').closest('.form-item').addClass('group-error');
      $('#edit-proposal-image-upload').closest('.form-item').append('<p class="group-error-description mrgn-tp-lg">The <strong>Proposal Image</strong> field is required.</p>')
      noErrors = false;
    }

    //Selects all invalid fields and adds parent
    setTimeout(function () {
      $('input.error, textarea.error').each(function () {
        var message = '<strong>' + $(this).attr('drupal-field-name') + '</strong> field is required.'
        $(this).closest('.form-item').addClass('group-error');
        $(this).closest('.form-item').append('<p class="group-error-description">' + message + '</p>')
      });

      //Add additional validation
      if ($('textarea').val().split(' ').length > 150) {
        if(!$('textarea').hasClass('error')) {
          $('textarea').addClass('error');
          var message = '<strong>' + $('textarea').attr('drupal-field-name') + '</strong> is over the word limit.'
          $('textarea').closest('.form-item').append('<p class="group-error-description">' + message + '</p>')
         }
      }

      //Check for valid email address
      if ($('#edit-primary-contact-email').val().length > 1 && !validateEmail($('#edit-primary-contact-email').val())) {
        if(!$('#edit-primary-contact-email').hasClass('error')) {
          $('#edit-primary-contact-email').addClass('error');
          $('#edit-primary-contact-email').closest('.form-item').addClass('group-error');
          var message = '<strong>' + $('#edit-primary-contact-email').attr('drupal-field-name') + '</strong> is not a valid email address.'
          $('#edit-primary-contact-email').closest('.form-item').append('<p class="group-error-description">' + message + '</p>')
        }
      }

      //Add messages to alert above. Show is error count is over 1
      if ($('.group-error-description').length > 0) {
        $('.alert-danger-list').show();
        $('.group-error-description').each(function () {
          $('.error-list').append('<li><a href="#' + $(this).parent().find('input, textarea').attr("id") + '">' + $(this).html() + '</a></li>');
        });
      }
    });

    return noErrors;

  }
})(jQuery);
