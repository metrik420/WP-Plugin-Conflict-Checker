// assets/js/script.js
document.addEventListener("DOMContentLoaded", function() {
    var sections = document.querySelectorAll(".conflict-section, .update-section");
    sections.forEach(function(section) {
        var header = section.querySelector("h2");
        header.style.cursor = "pointer";
        header.addEventListener("click", function() {
            var content = section.querySelector("ul, p");
            if (content.style.display === "none") {
                content.style.display = "block";
            } else {
                content.style.display = "none";
            }
        });
    });
});

