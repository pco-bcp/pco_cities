// Custom mobile menu
$ = jQuery;

$(document).ready(function () {
    $("#toggle-menu").click(function () {
        $(".menu").toggleClass("menu-mobile-visible");
    });
});

// Parallax effects
var rellax = new Rellax('.rellax');

// Scroll Reveal effects
ScrollReveal().reveal('.card', {
    interval: 16,
    reset: false,
    duration: 1250,
    scale: 0.5
});
