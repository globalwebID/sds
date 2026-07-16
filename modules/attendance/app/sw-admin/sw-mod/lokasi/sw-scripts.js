'use strict';
function loading(){
    $('.btn-save').prop("disabled", true);
      // add spinner to button
      $('.btn-save').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
     window.setTimeout(function () {
      $('.btn-save').prop("disabled", false);
      $('.btn-save').html('<i class="far fa-save"></i> Simpan'
      );
    }, 2000);
}


/** Module User/Pegawai */
loadData();
function loadData(){
    var modifikasi = $('.modifikasi').html();
    var hapus = $('.hapus').html();
    var table;
    $(document).ready(function() {
        //datatables
        table = $('.datatable-lokasi').DataTable({
            "fnDrawCallback": function () {
                $('.open-popup-link').magnificPopup({
                type: 'image',
                removalDelay: 300,
                mainClass: 'mfp-fade',
                    gallery: {
                        enabled: true
                    },
                    zoom: {
                        enabled: true,
                        duration: 300,
                        easing: 'ease-in-out',
                        opener: function (openerElement) {
                            return openerElement.is('img') ? openerElement : openerElement.find('img');
                        }
                    }
                });
            },
            "processing": true, 
            "serverSide": false, 
            "bAutoWidth": true,
            "bSort": false,
            "bStateSave": true,
            "bDestroy" : true,
            "paging": true,
            "ssSorting" : [[0, 'desc']],
            "iDisplayLength": 25,
            "order": [],
            "aLengthMenu": [
                [25, 30, 50, -1],
                [25, 30, 50, "All"]
            ],
            language: {
              paginate: {
                previous: "<i class='fas fa-angle-left'>",
                next: "<i class='fas fa-angle-right'>"
              }
            },
            "ajax": {
                "url": "./sw-mod/lokasi/sw-datatable.php",
                "type": "POST",
                "data": {
                    modifikasi:modifikasi,
                    hapus:hapus,
                 }, 
            },
 
            "columnDefs": [{ 
                "targets": [ 0 ], 
                "orderable": false, 
            },],
        });
    });
}



/** Map Leatfet  */
function lokasi(){
navigator.geolocation.getCurrentPosition(function(location) {
  var latitude_input  = parseFloat($('.latitude').val());
  var longitude_input = parseFloat($('.longitude').val());
    
    var defaultLocation;
    if (isNaN(latitude_input) || isNaN(longitude_input)) {
      defaultLocation = [location.coords.latitude, location.coords.longitude];
    } else {
      defaultLocation = [latitude_input, longitude_input];
    }

  // const defaultLocation = [-5.372037865789907, 105.271263];
   const map = L.map('map').setView(defaultLocation, 13);
   map.attributionControl.setPrefix(false);

   // Tile layer
   L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

   // Marker draggable
   const marker = L.marker(defaultLocation, { draggable: true }).addTo(map);

   // Input updater
   function updateLatLngInputs(lat, lng) {
     document.getElementById('lat').value = lat.toFixed(6);
     document.getElementById('lng').value = lng.toFixed(6);
   }
   

   async function updateAddressFromLatLng(lat, lng, distance = null, isValid = null) {
     const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
     const data = await res.json();
     const address = data.display_name || '';
     document.getElementById('alamat').value = address;

     // Tampilkan popup
     let popupContent = `<b>Lokasi Anda</b><br>${address}`;
     if (distance !== null && isValid !== null) {
       popupContent += `<br>Jarak: ${Math.round(distance)} meter<br>${isValid ? "✅ Lokasi valid" : "❌ Lokasi tidak valid (mungkin fake GPS)"}`;
     }

     marker.bindPopup(popupContent).openPopup();

   }

   // Init awal
   updateLatLngInputs(defaultLocation[0], defaultLocation[1]);
   updateAddressFromLatLng(defaultLocation[0], defaultLocation[1]);

   // Drag marker
   marker.on('dragend', function () {
     const pos = marker.getLatLng();
     updateLatLngInputs(pos.lat, pos.lng);
     updateAddressFromLatLng(pos.lat, pos.lng);
   });

   // Locate control
   L.control.locate({
     setView: 'once',
     keepCurrentZoomLevel: true,
     flyTo: true,
     strings: {
       title: "Lokasi Saya"
     },
     onLocationError: function(err) {
       alert(err.message);
     }
   }).addTo(map);

   map.on('locationfound', function (e) {
     const lat = e.latitude || e.latlng.lat;
     const lng = e.longitude || e.latlng.lng;
     marker.setLatLng([lat, lng]);
     map.setView([lat, lng], 15);
     updateLatLngInputs(lat, lng);
     updateAddressFromLatLng(lat, lng);
   });

   // Autocomplete
   document.getElementById('search').addEventListener('input', async function (e) {
     const query = e.target.value;
     const suggestionBox = document.getElementById('suggestions');
     if (query.length < 3) {
       suggestionBox.innerHTML = '';
       return;
     }

     const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5`);
     const data = await res.json();

     suggestionBox.innerHTML = '';
     data.forEach((place) => {
       const li = document.createElement('li');
       li.textContent = place.display_name;
       li.addEventListener('click', () => {
         document.getElementById('search').value = place.display_name;
         suggestionBox.innerHTML = '';
         const lat = parseFloat(place.lat);
         const lon = parseFloat(place.lon);
         map.setView([lat, lon], 15);
         marker.setLatLng([lat, lon]);
         updateLatLngInputs(lat, lon);
         updateAddressFromLatLng(lat, lon);
       });
       suggestionBox.appendChild(li);
     });
   }); 

});       
}

  /** Tambah Lokasi */
    $(".form-add").validate({
        // Specify validation rules
        rules: {
            field: {
                required: true
            },
        
            alamat: {
                required: true,
                minlength: 10,
                maxlength: 150
            }
        },

        // Specify validation error messages
        messages: {
            field: {
                required: "Silahkan masukkan data sesuai inputan",
            },
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Add
      });

    /* handle form submit */
    function submitForm_Add() { 
        var data = $(".form-add").serialize();
        $.ajax({    
            type : 'POST',
            url  : './sw-mod/lokasi/sw-proses.php?action=add',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                    $(".form-add").trigger("reset");
                    window.setTimeout(window.location.href = "./lokasi",2500);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
            return false; 
    }


    /* ------ Update Lokasi ------- */
    $(".form-update").validate({
        // Specify validation rules
        rules: {
          field: {
              required: true
          },
      
          alamat: {
              required: true,
              minlength: 10,
              maxlength: 150
          }
      },

        // Specify validation error messages
        messages: {
            field: {
                required: "Silahkan masukkan data sesuai inputan",
            },
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Update
      });

    /* handle form submit */
    function submitForm_Update() { 
        var data = $(".form-update").serialize();
        $.ajax({    
            type : 'POST',
            url  : './sw-mod/lokasi/sw-proses.php?action=update',
            data : data,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan!', icon: 'success', timer: 2500,});
                    $(".form-add").trigger("reset");
                    //window.setTimeout(window.location.href = "./user",2500);
                    setTimeout(function(){history.back();}, 3000);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
            return false; 
    }


    /* ------------- Set Active Lokasi --------------*/
    $(document).on('click', '.btn-active', function(){
    var id = $(this).attr("data-id");
    var active = $(".active"+id).attr("data-active");
    if(active == "Y"){
        var dataactive = "N";
    }else{
        var dataactive = "Y";
    }
     var dataString = 'id='+ id + '&active='+ dataactive;
    $.ajax({
        type: "POST",
        url: "./sw-mod/lokasi/sw-proses.php?action=active",
        data: dataString,
        success: function (data) {
            if(active == "Y"){
                $(".active"+id).attr("data-active","N");
            }else{
                $(".active"+id).attr("data-active","Y");
            }

          if (data == 'success') {
                console.log('Successfully set active');
            }else{
               console.log(data);
            }
        }
    });
  });


/** Hapus data Lokasi */
$(document).on('click', '.btn-delete', function(){ 
    var id = $(this).attr("data-id");
   
      swal({
        text: "Anda yakin ingin menghapus data ini?",
        icon: "warning",
          buttons: {
            cancel: true,
            confirm: true,
          },
        value: "yes",
      })

      .then((value) => {
        if(value) {
            loading();
            $.ajax({  
                 url:'./sw-mod/lokasi/sw-proses.php?action=delete',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                        swal({title: 'Berhasil!', text: 'Data berhasil dihapus.!', icon: 'success', timer: 2500,});
                        loadData();
                    } else {
                        swal({title: 'Gagal!', text: data, icon: 'error', timer:2500,});
                        
                    }
                 }  
            });  
       } else{  
        return false;
    }  
});
}); 
    