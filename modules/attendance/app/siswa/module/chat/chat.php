<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:../404');
}else{
echo'
<div class="chatbox">
    <!-- frend list-->
   <div class="friendslist">
        <div class="topmenu">
            <h4>Obrolan langsung</h4>
            <span class="material-icons close btn-close-chat">close</span>
        </div>

        <div class="friend">
            <ul class="load-data-frendlist">
                
            </ul>
        </div>
        <div class="search">
	         <input type="text" class="searchfield search-frendlist" placeholder="Cari Pengajar.." value="">
         </div>
   </div>
   <!-- End Frend List -->

   <!-- Mesage -->
   <div class="chatview" style="display:none">
        <div class="top-meu-profile">
            <div class="avatar">
                <img class="avatar-chat" src="" height="40">
            </div>
            <p><strong class="nama-lengkap"></strong>
            <span class="status-time">Aktif 3 Jam</span></p>
            <span class="material-icons close close-live-chat">close</span>
        </div>
            

        <div class="chat-messages">
            <div class="load-data-message"></div>
            <div class="loading-pesan text-center"></div>
        </div>

        <div class="sendmessage">
            <form class="form-add-chat" role="form" action="javascript:void(0);" autocomplete="off">
                <input type="hidden" class="parent-user" name="pegawai" readonly required>
                <textarea class="emojioneArea send pesan" name="pesan" placeholder="Send message..."></textarea>
                <button type="submit" class="button btn-send"><spabn class="material-icons">send</span></button>
            </form>
        </div>
    </div>

</div>';
}?>