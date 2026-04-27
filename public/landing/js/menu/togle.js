function toggleDescription() {
    const descText = document.getElementById("descText");
    const btn = document.getElementById("readMoreBtn");

    // Toggle class 'expanded' pada elemen teks
    descText.classList.toggle("expanded");

    // Cek apakah class 'expanded' aktif atau tidak untuk mengubah teks tombol
    if (descText.classList.contains("expanded")) {
        btn.innerHTML = "Lebih Sedikit ↑";
    } else {
        btn.innerHTML = "Baca Selengkapnya ↓";
    }
}

// navbar-baru
// Dropdown nav
function toggleDropdown() {
    const btn = document.getElementById("avatarBtn");
    const dd = document.getElementById("userDropdown");
    const open = dd.classList.toggle("open");
    btn.classList.toggle("open", open);
}
document.addEventListener("click", (e) => {
    const wrap = document.querySelector(".nav-avatar-wrap");
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById("avatarBtn").classList.remove("open");
        document.getElementById("userDropdown").classList.remove("open");
    }
});
