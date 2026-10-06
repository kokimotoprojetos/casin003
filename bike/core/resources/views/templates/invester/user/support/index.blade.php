@extends($activeTemplate . 'layouts.master')
@section('content')
   
   
      
<style>
         .heading_label {
            border-radius: 0px 0px 0px 47px;
            background-color: rgba(63, 63, 63, 1);
        }
      
        .service-sub-heading{
          font-family: 'Inter', sans-serif;
          font-size: 11px;
          font-weight: 500;
          line-height: 13px;
          letter-spacing: 0.06em;
          text-align: left;

        }
        .social-btn{
          box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.18);
          border-radius: 10px;
        }
        .social-btn-top{
          border-radius: 35px;
          background-color: white;
          box-shadow: 0px 0px 3.279069662094116px 0px #0000002E;

        }

        .social-btn-icon{
          width: 48px;
          height: 48px;
        }
        .social-btn-label{
          font-family: 'Inter', sans-serif;
font-size: 12px;
font-weight: 500;
line-height: 15px;
letter-spacing: 0em;
text-align: center;

        }
        .header-img-container {
border-radius: 20px;              height: 165px;
            position: relative; 
            text-align: center; 
            background-image: url('https://perview.freelancerawais.online/cyclepro/core/img/customer-header.webp');
      background-size: cover;
            /* margin-top: -60px; */
        }
</style>

  <div class="text-center mt-3">
    <h2 style="font-size: 18px; font-weight: 700; color:#000000;">Atendimento ao Cliente</h2>
</div>
<div class="header-img-container m-3">
</div>

<div class="d-flex justify-content-around">
  <button onclick="navigateToWhatsApp()" class="social-btn-top d-flex justify-content-between mt-2 ps-2 pe-4" style="height: 56px;">
      
      
      
      
      
    <!-- <button onclick="navigateToWhatsApp()">  -->
    <div class="pt-1" onclick="window.location.href='https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo'" style="cursor:pointer">
      <img src="{{asset ('core/img/whatsapp1.webp')}}" class="social-btn-icon">
    </div>
    
    
    <div class="align-self-center ps-2">
        <span class="social-btn-label" onclick="window.location.href='https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo'">Whatsapp</span>
        <p class="service-sub-heading"  >+92xxxxxxxx</p>
    </div>
  <!-- </button> -->
  
  
  
</button>


<button class="social-btn-top d-flex justify-content-between mt-2 ps-1 pe-4" style="height: 56px;" onclick="navigateToTelegramGroup()">
  <div class="p-1" onclick="window.location.href='https://t.me/WindsBikes0001'" style="cursor:pointer">
    <img src="{{asset ('core/img/telegram.webp')}}"  class="social-btn-icon">
  </div>
  <div class="align-self-center ps-2" onclick="navigateToTelegramGroup()">
      <span class="social-btn-label">Canal do <br> Telegram</span>
  </div>

</button>
</div>



<div class="m-3 mt-3">
   <div class="d-flex justify-content-between m-1 p-3" style="background: #9400FF; border-radius: 10px;;">
    <div>
        <p class="service-sub-heading text-white">Tem uma duvida? Fale conosco</p>
    </div>
    <div>
        <p class="service-sub-heading  text-white">horario: 10:00--18:00</p>
    </div>
   </div>
    <div class="social-btn d-flex justify-content-between mt-4" style="height: 66px;">
        <div class="m-2" onclick="window.location.href='https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo'" style="cursor:pointer">
          <img src="{{asset ('core/img/whatsapp1.webp')}}" class="social-btn-icon">
        </div>
        <div class="align-self-center">
            <span class="social-btn-label"  onclick="window.location.href='https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo'">Whatsapp</span>
            <!-- <p class="service-sub-heading" style="color: #67D449;" >+923044988544</p> -->
        </div>
        <div class="m-4">
            <button style="background-color: transparent;" onclick="navigateToWhatsApp()">
            
            </button>
        </div>
    </div>

    <div class="social-btn d-flex justify-content-between mt-2" style="height: 66px;">
      <div class="m-2" onclick="window.location.href='https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo'" style="cursor:pointer">
        <img src="{{asset ('core/img/whatsapp-group.webp')}}" class="social-btn-icon">
      </div>
      <div class="align-self-center">
          <span class="social-btn-label" onclick="window.location.href='https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo'">Grupo Whatsapp</span>
        </div>
        <div class="m-4">
          <button style="background-color: transparent;" onclick="navigateToGroup()">
            <!-- Join -->
 
          </button>
      </div>
  </div>
    <div class="social-btn d-flex justify-content-between mt-2" style="height: 66px;" onclick="navigateToTelegramGroup()">
      <div class="m-2" onclick="window.location.href='https://t.me/WindsBikes0001'" style="cursor:pointer">
        <img src="{{asset ('core/img/telegram.webp')}}"  class="social-btn-icon">
      </div>
      <div class="align-self-center" onclick="window.location.href='https://chat.whatsapp.com/Kc5nYJhxYFR3OsjrUgxiKo'">
          <span class="social-btn-label">Canal do Telegram</span>
      </div>
      <div class="m-4">
          <button style="background-color: transparent;">             
          </button>
      </div>
  </div>
</div>

   
   
   
<script>
    function navigateToTelegramGroup() {
        window.location.href = '{{ "https://t.me/WindsBikes0001" }}';
    }
</script>
@endsection

