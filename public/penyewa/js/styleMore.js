document.addEventListener("DOMContentLoaded", function () {
   
    var content = document.querySelector(".content");
    var container = document.querySelector(".container");
    var readMoreBtn = document.getElementById("readMoreBtn");
    
    readMoreBtn.addEventListener("click", function () {
        container.classList.toggle("expanded");
       
        if (container.classList.contains("expanded")) {
            readMoreBtn.textContent = "Baca Lebih Sedikit";
            // console.log(readMoreBtn.textContent)
        } else {
            readMoreBtn.textContent = "Baca Selengkapnya";
        }
    });
});