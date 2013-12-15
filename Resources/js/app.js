;(function (window, document, $, undefined) {

  var App = {};
  App.subscriptions = {};
  App.Data = {};
  App.Modules = {};
  App.queue = [];

  window.log = function f() {
    log.history = log.history || [];
    log.history.push(arguments);

    if (this.console) {
      var args = arguments, newarr;
      args.callee = args.callee.caller;
      newarr = [].slice.call(args);

      if (typeof console.log === 'object') {
        log.apply.call(console.log, console, newarr);
      }
      else {
        console.log.apply(console, newarr);
      }
    }
  };

  App.publish = function (topic, args) {
    App.subscriptions[topic] && $.each(App.subscriptions[topic], function () {
      this.apply(App, args || []);
    });
  };

  App.subscribe = function (topic, callback) {
    if (!App.subscriptions[topic]) {
      App.subscriptions[topic] = [];
    }
    App.subscriptions[topic].push(callback);
    return [topic, callback];
  };

  App.unsubscribe = function (handle) {
    var t = handle[0];
    App.subscriptions[t] && $.each(App.subscriptions[t], function (idx) {
      if (this == handle[1]) {
        App.subscriptions[t].splice(idx, 1);
      }
    });
  };

  App.Data.DropdownTimer = null;
  App.Modules.Dropdown = function() {

    $('.dropdown-wrapper, .user-bar-dropdown').each(function (i, el) {
      var dropdownTimer,
          $el = $(el),
          dropdown = $el.find('.dropdown:first');

      $el.on('mouseenter', function (e) {
        log('mouseenter');
        clearTimeout(dropdownTimer);
        dropdown.show();
      });

      $el.on('mouseleave', function (e) {
        log('mouseleave');
        dropdownTimer = setTimeout(function () {
          log('mouseleave closed');
          dropdown.hide();
        }, 240);
      });

    });

  };

  App.Modules.Nav = {
    init: function() {
      var resizeTimer,
          preResizeWidth = $(window).width(),
          navList = $('.nav-global'),
          navDropdown = $('.nav-dropdown');

      if (!navList || !navDropdown) {
        return;
      }

      $('.nav-dropdown-link').on('click', function (e) {
        navDropdown.toggle();
      });

      $('.has-sub-nav > a').on('click', function (e) {
        var itemDropdown = $(this).next('ul');
        if (itemDropdown.is(':visible')) {
          itemDropdown.css('display', '');
        } else {
          itemDropdown.show();
        }
      });

      App.Modules.Nav.checkItems(navList, navDropdown);
//      App.Modules.Nav.hideColumns(navList, navDropdown);
      App.Modules.Nav.toggleLink();

      $(window).resize(function() {
        clearTimeout(resizeTimer);
        navList.css('overflow', 'hidden');
        resizeTimer = setTimeout(function () {
          log('check');
          App.Modules.Nav.checkItems(navList, navDropdown);
          preResizeWidth = $(window).width();
          navList.css('overflow', "");
          return;
          if ($(window).width() < preResizeWidth) {
            App.Modules.Nav.hideColumns(navList, navDropdown);
          } else {
            App.Modules.Nav.showColumns(navList, navDropdown);
          }
          preResizeWidth = $(window).width();
        }, 100);
      });
    },
    checkItems: function(navList, navDropdown) {
      var navListItems = navList.children().length,
          navListItemFirst = navList.children(':first'),
          firstPos = navListItems ? navList.children(':first').offset().top : 0;

//      log('first', firstPos);
//      log('navListItemFirst', navListItemFirst);
//      log('navListItemFirst offset', navListItemFirst.offset().top);

      if (!navListItems) {
        alert('no items');
        return;
      }

      if (verge.viewportW() >= 768 && verge.viewportH() >= 400) {
        navDropdown.hide();
        navDropdown.children().each(function(i, el) {
          navList.append(el);
        });
//        log('finding..', navList.find('> .has-sub-nav > ul'), navList.find('> .has-sub-nav > ul:visible'));
        navList.find('> .has-sub-nav > ul:visible').css('display', '');
        App.Modules.Nav.toggleLink();
        return;
      }

//      log(navList.children());
//      log($(navList.children().get().reverse()));
      $(navList.children().get().reverse()).each(function(i, el) {
//        log(navListItemFirst, $(el), $(el).offset().top, firstPos, i, navListItems);
//        log('navListItemFirst offset x', navListItemFirst.offset().top);
        if ($(el).offset().top == navListItemFirst.offset().top || (i + 1 == navListItems)) {
          return false;
        }

        navDropdown.prepend(el);
      });

      if (navDropdown.children().length) {
        navDropdown.children().each(function(i, el) {
          navList.append(el);
//          log('added');

          if ($(el).offset().top !== navListItemFirst.offset().top && (verge.viewportW() < 768 || verge.viewportH() < 400)) {
            navDropdown.prepend(el);
            return false;
          }
        });
      }

      App.Modules.Nav.toggleLink();
    },
    showColumns: function(navList, navDropdown) {
      var firstPos = navList.children(':first').offset().top;

      if (verge.viewportW() >= 768 && verge.viewportH() >= 400) {
        navDropdown.hide();
      }

      navDropdown.children().each(function(i, el) {
        navList.append(el);

        if ($(el).offset().top !== firstPos && (verge.viewportW() < 768 || verge.viewportH() < 400)) {
          navDropdown.prepend(el);
          return false;
        }
      });

      App.Modules.Nav.toggleLink();
    },
    hideColumns: function(navList, navDropdown) {
      var firstPos = navList.children(':first').offset().top;

      if (verge.viewportW() >= 768 && verge.viewportH() >= 400) {
        navDropdown.children().each(function(i, el) {
          navList.append(el);
        });
        App.Modules.Nav.toggleLink();
        return;
      }

      $(navList.children().get().reverse()).each(function(i, el) {
        if ($(el).offset().top == firstPos) {
          return false;
        }

        navDropdown.prepend(el);
      });
      App.Modules.Nav.toggleLink();
    },
    toggleLink: function() {
      if ($('.nav-dropdown').children().length) {
        $('.nav-dropdown-link').show();
      } else {
        $('.nav-dropdown-link').hide();
      }
    }
  };

  App.subscribe("init", function () {

    App.Modules.Nav.init();
//      App.Modules.Dropdown();
  });

  App.exec = function (controller, action) {
    var action = ( action === undefined ) ? "init" : action;

    if (controller !== "" && App[controller] && typeof App[controller][action] == "function") {
      App[controller][action]();
    }
  };

  App.init = function () {
    var controller = document.body.getAttribute("data-controller"),
        action = document.body.getAttribute("data-action");

    if (typeof controller == 'string') {
      App.exec(controller);
      App.exec(controller, action);
    }
  };

  App.checkout = {
    init: function () {
    },
    create: function () {
    }
  };


  $(function ($) {
    App.publish("init");
    App.init();
  });

  $(window).unload(function () {
    App.publish("destroy");
  });

  window.App = App;

})(window, document, jQuery);
