$(document).ready(function() {

  $('#signUpForm')[0].reset();
  $('#loginForm')[0].reset();

  var loggedUser = window.localStorage.getItem('user');
  if (loggedUser) {
    loggedUser = JSON.parse(loggedUser);
    $('.user').show();
    $('.guest').hide();

    var currentUser = getUserById(loggedUser.id);
    // if(currentUser){
    //   if (loggedUser.role_name == 'Admin') {
    //     $('#logoutBtn').text('Logout('+ currentUser.first_name +') (Admin)');
    //   } else {
    //     $('#logoutBtn').text('Logout('+ currentUser.first_name +')');
    //   }
    // }

  } else {
    loggedUser = null;
    $('.guest').show();
    $('.user').hide();
  }

  $('#logoutBtn').click(function() {
    logout();
  })

  function tryLogin(users, email, password) {
    for (var user of users) {
      if (user.email == email && user.password == password) {
        return user;
      }
    }
    return null;
  }

  function logout() {
    $.LoadingOverlay("show");
    window.localStorage.clear();
    window.location = 'index.html';
  }

  function getUserById(id) {
    $.LoadingOverlay("show");
    $.ajax({
      url: 'rest/user?id='+loggedUser.id,
      method: 'GET',
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-Authorization', loggedUser.token)
      },
      success: function(result) {
        console.log(result);
        if (loggedUser.role_name == 'Admin') {
          $('#logoutBtn').text('Logout ('+ result[0].first_name +' - Admin)');
        } else {
          $('#logoutBtn').text('Logout ('+ result[0].first_name +')');
        }
        $.LoadingOverlay("hide");
      },
      error: function(error) {
        if (error.status == 403) {
        $('#loginModal').toggle();
        }
        $.LoadingOverlay("hide");
        toastr.error('Something went wrong.');
      }
    });
  }

  $('#loginForm').validate({
    rules: {
      email: {
        required: true,
      },
      password: {
        required: true,
        minlength: 6,
      }
    },
    messages: {
      email: {
        required: "Please enter your email address."
      },
      password: {
        required: "Please enter your password.",
        minlength: jQuery.validator.format("Please, at least {0} characters are necessary")
      }
    },
    submitHandler: function(form, validator) {
      //ajax
      $.LoadingOverlay("show");

      var user = {
        email: $('#loginEmail').val(),
        password: $('#loginPw').val()
      };

      $.post("rest/login", user).done(function(data) {
        $.LoadingOverlay("hide");
        toastr.success("Logged in");
        $('#loginModal').modal('toggle');
        localStorage.setItem("user", JSON.stringify(data));
        setTimeout(function() {
          window.location.reload();
        }, 1500);

      }).fail(function(error) {
        $.LoadingOverlay("hide");
        toastr.error("Login Failed!");
      });
    }
  });

  $('#signUpForm').validate({
    rules: {
      firstName: {
        required: true
      },
      lastName: {
        required: true
      },
      signUpEmail: {
        required: true,
      },
      signUpPassword: {
        required: true,
        minlength: 6
      },
      signUpConfirmPassword: {
        minlength: 6,
        equalTo: '#mainPassword'
      }
    },
    messages: {
      signUpEmail: {
        required: "Please enter your email address."
      },
      signUpPassword: {
        minlength: jQuery.validator.format("Please, at least {0} characters are necessary")
      },
      signUpConfirmPassword: {
        minlength: jQuery.validator.format("Please, at least {0} characters are necessary"),
        equalTo: "Please confirm your password."
      }
    },
    submitHandler: function(form, validator) {
      $.LoadingOverlay("show");
      $.ajax({
        type: 'POST',
        url: 'rest/register',
        data: $("#signUpForm").serialize(),
        success: function(result) {
          $.LoadingOverlay("hide");
          $('#signUpModal').modal('toggle');
          toastr.success("Sign up successful!");

          $('#loginEmail').val($('#userEmail').val());
          $('#loginModal').modal('toggle');
          $('#signUpForm')[0].reset();
        },
        error: function() {
          $.LoadingOverlay("hide");
          toastr.error('Sign up failed!');
        }
      });
    }
  });
});
