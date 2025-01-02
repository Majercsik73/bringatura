//<!-- GoToTop button vezérlése -->
var mybutton = document.getElementById("myBtn");

 // 500px-nél nagyobb görgetés esetén megjelenik a gomb
window.onscroll = function() {scrollFunction()};

function scrollFunction() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        mybutton.style.display = "block";
    } else {
        mybutton.style.display = "none";
    }
}
// onclick függvény
function topFunction() {
    document.body.scrollTop = 0; // Safari böngésző
    document.documentElement.scrollTop = 0; // Chrome, IE 8+ böngésző
}