$('a[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
    const activeTabId = $(e.target).attr("href"); // Ambil ID tab yang aktif
    localStorage.setItem("activeTab", activeTabId);
});
$(document).ready(function () {
    const activeTabId = localStorage.getItem("activeTab");

    if (activeTabId) {
        // Aktifkan tab yang disimpan dalam local storage
        $('a[href="' + activeTabId + '"]').tab("show");
    } else {
        // Aktifkan tab default (misalnya tab pertama)
        $('a[data-bs-toggle="tab"]').first().tab("show");
    }
});

$(document).on("show.bs.modal", "#updateTalent", function (e) {
    var tombol = $(e.relatedTarget);
    var uid = tombol.data("uid");
    var talent = tombol.data("talent");
    var modal = $(this);
    modal.find("#uidTalent").val(uid);
    modal.find("#namaTalent").val(talent);
});
$(document).on("show.bs.modal", "#updateHarga", function (e) {
    var tombol = $(e.relatedTarget);
    var id = tombol.data("id");
    var harga = tombol.data("harga");
    var kategori = tombol.data("kategori");
    var qty = tombol.data("qty");
    var modal = $(this);
    modal.find("#idHarga").val(id);
    modal.find("#updateHarga").val(harga);
    modal.find("#qtyHarga").val(qty);
    modal.find("#kategoriHarga").val(kategori);
});
$(document).on("show.bs.modal", "#updateSlide", function (e) {
    var tombol = $(e.relatedTarget);
    var uid = tombol.data("uid");
    var title = tombol.data("title");
    var sort = tombol.data("sort");
    var url = tombol.data("url");
    var modal = $(this);
    modal.find("#uidSlide").val(uid);
    modal.find("#titleSlide").val(title);
    // modal.find('#sortSlide').val(sort)
    modal.find("#urlSlide").val(url);
    $("#sortSelect").val(sort);
});
$(document).on("show.bs.modal", "#updateTerm", function (e) {
    var tombol = $(e.relatedTarget);
    var uid = tombol.data("uid");
    var title = tombol.data("title");
    var term = tombol.data("term");
    var modal = $(this);
    modal.find("#termUid").val(uid);
    modal.find("#termTitle").val(title);
    modal.find("#termTerm").text(term);
});
$(document).on("show.bs.modal", "#upPartner", function (e) {
    var tombol = $(e.relatedTarget);
    var uid = tombol.data("uid");
    var name = tombol.data("name");
    var email = tombol.data("email");
    var city = tombol.data("city");
    var alamat = tombol.data("alamat");
    var nomor = tombol.data("nomor");
    var modal = $(this);
    modal.find("#uid").val(uid);
    modal.find("#name").val(name);
    modal.find("#email").val(email);
    modal.find("#kota").val(city);
    modal.find("#alamat").val(alamat);
    modal.find("#nomor").val(nomor);
});
$(document).on("show.bs.modal", "#updateVoucher", function (e) {
    var tombol = $(e.relatedTarget);

    var id = tombol.data("id");
    var code = tombol.data("code");
    var unit = tombol.data("unit");
    var nominal = tombol.data("nominal");
    var minbeli = tombol.data("minbeli");
    var maxDisc = tombol.data("maxdisc");
    var limit = tombol.data("limit");
    var eventUid = tombol.data("event"); // Tambahkan event data
// console.log(id)
    var modal = $(this);
    modal.find("#id").val(id);
    modal.find("#codeV").val(code);

    // Set radio buttons untuk unit
    if (unit === "rupiah") {
        modal.find("#URupiah").prop("checked", true);
        modal.find("#nominalRupiah").val(nominal);
    } else {
        modal.find("#UPersen").prop("checked", true);
        modal.find("#nominalPersen").val(nominal);
    }

    modal.find("#minV").val(minbeli);
    modal.find("#maxV").val(maxDisc);
    modal.find("#maxUseV").val(limit);

    // Set event UID di select dropdown
    modal.find("select[name='event']").val(eventUid); // Pastikan nilai 'eventUid' sesuai dengan option value
});


function toggleNom() {
    const isRupiahChecked =
        document.querySelector('input[name="unit"]:checked').value === "rupiah";
    console.log("isRupiahChecked:", isRupiahChecked);
    console.log(
        "inputrupiah display:",
        document.getElementById("inputrupiah").style.display
    );
    console.log(
        "inputpersen display:",
        document.getElementById("inputpersen").style.display
    );

    document.getElementById("inputrupiah").style.display = isRupiahChecked
        ? "flex"
        : "none";
    document.getElementById("inputpersen").style.display = isRupiahChecked
        ? "none"
        : "flex";
}

$(document).ready(function () {
    $(document).on("click", ".delete", function () {
        var getLink = $(this).attr("href");
        Swal.fire({
            title: "Yakin hapus data?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Ya",
            cancelButtonColor: "#3085d6",
            cancelButtonText: "Batal",
        }).then((result) => {
            //jika klik ya maka arahkan ke proses.php
            if (result.isConfirmed) {
                window.location.href = getLink;
            }
        });
        return false;
    });
});

// document.addEventListener("DOMContentLoaded", function () {
//     var content = document.querySelector(".content");
//     var container = document.querySelector(".container");
//     var readMoreBtn = document.getElementById("readMoreBtn");

//     readMoreBtn.addEventListener("click", function () {
//         container.classList.toggle("expanded");

//         if (container.classList.contains("expanded")) {
//             readMoreBtn.textContent = "Baca Lebih Sedikit";
//         } else {
//             readMoreBtn.textContent = "Baca Selengkapnya";
//         }
//     });
// });
