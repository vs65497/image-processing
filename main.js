function main() {
    var pixels = document.getElementsByClassName('pixel');
    var display_coords = document.getElementsByClassName('coords')[0];

    for(var i=0;i<pixels.length;i++) {
        pixels[i].onmouseover = function () {
            var coords = this.getAttribute('coords');
            display_coords.innerHTML = coords;
        }
    }
}

window.onload = function() {
    main();
}