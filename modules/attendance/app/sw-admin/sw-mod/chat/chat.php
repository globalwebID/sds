<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{

echo'
<div class="chatbox">
    <!-- frend list-->
   <div class="friendslist">
        <div class="topmenu">
            <ul>
                <li class="active"><span class="fas fa-users"></span></li>
            </ul>
            <span class="fas fa-times close btn-close-chat"></span>
        </div>

        <div class="friend">
            <ul class="load-data-frendlist">
                
            </ul>
        </div>
        <div class="search">
	         <input type="text" class="searchfield search-frendlist" placeholder="Cari orang tua murid.." value="">
         </div>
   </div>
   <!-- End Frend List -->

   <!-- Mesage -->
   <div class="chatview" style="display:none">
        <div class="top-meu-profile">
            <div class="avatar">
                <img class="avatar-chat" src="" height="50">
            </div>
            <p><strong class="nama-lengkap"></strong>
            <span class="status-time">Aktif 3 Jam</span></p>
            <span class="fas fa-times close close-live-chat"></span>
        </div>
            

        <div class="chat-messages">
            <div class="load-data-message"></div>
            <div class="loading-pesan text-center"></div>
        </div>

        <div class="sendmessage">
            <form class="form-add-chat" role="form" action="javascript:void(0);" autocomplete="off">
                <input type="hidden" class="parent-user" name="parent_id" readonly required>
                <input type="text" class="pesan" name="pesan" placeholder="Send message...">
                <button type="submit" class="button"><spabn class="fas fa-paper-plane"></span></button>
            </form>
        </div>
    </div>

</div>';
}?>