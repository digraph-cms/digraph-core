$(() => {
  let $standardMenu = $('#digraph-navbar').eq(0);
  let $mobileMenu = $('<div id="digraph-mobile-menu" class="digraph-area closed"></div>');
  let $mobileMenuButton = $('<a class="toggle-button">Menu</a>');
  let $mobileMenuLinks = $('<div class="links closed"></div>');
  $mobileMenu.append($mobileMenuButton);
  $mobileMenu.append($mobileMenuLinks);
  $mobileMenu.hide();
  let menuState = 'standard';
  $standardMenu.after($mobileMenu);
  //listener to make button toggle mobile menu
  $mobileMenuButton.on('click',(e)=>{
    $mobileMenuLinks.toggleClass('closed');
    if (!$mobileMenuLinks.hasClass('closed')) {
      $mobileMenuLinks.height($mobileMenuLinks[0].scrollHeight);
      $mobileMenu.removeClass('closed');
    }else {
      $mobileMenuLinks.height(0);
      $mobileMenu.addClass('closed');
    }
  });
  //check if menu needs toggling
  let checkMenu = () => {
    if (menuState == 'standard' && $standardMenu[0].offsetWidth < $standardMenu[0].scrollWidth) {
      mobileMenu();
    }
    if (menuState == 'mobile' && $standardMenu[0].offsetWidth >= $standardMenu[0].scrollWidth) {
      standardMenu();
    }
  };
  //switch to mobile menu
  let mobileMenu = () => {
    menuState = 'mobile';
    //hide standard menu
    $standardMenu.attr('style','overflow:hidden;margin:0;padding:0;border:0;outline:0;');
    $standardMenu.height(0);
    //copy links and show mobile menu
    $mobileMenuLinks.empty();
    $mobileMenuLinks.append($standardMenu.find('.menuitem').clone());
    $mobileMenuLinks.height($mobileMenuLinks[0].scrollHeight);
    $mobileMenu.show();
};
//switch to standard menu
let standardMenu = () => {
  menuState = 'standard';
  $standardMenu.attr('style','');
  $mobileMenu.hide();
};
//event listeners, plus immediate check
checkMenu(); setInterval(checkMenu, 1000); $(window).on('resize', checkMenu);
});
