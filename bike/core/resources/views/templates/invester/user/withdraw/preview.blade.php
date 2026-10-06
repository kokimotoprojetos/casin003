@extends($activeTemplate.'layouts.master')
@section('content')



    <title>
         Saque
    </title>
    <script>
        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == "interactive") {
                // Show the preloader when the page starts loading
                showPreloader();
            } else if (state == "complete") {
                // Hide the preloader when the page finishes loading
                hidePreloader();
            }
        };

        function showPreloader() {
            document.getElementById("preloader").style.display = "flex";
            document.getElementById("blurred-background").style.display = "block";
        }

        function hidePreloader() {
            document.getElementById("preloader").style.display = "none";
            document.getElementById("blurred-background").style.display = "none";
        }

    </script>



<style>
  * {
    font-family: 'Inter', sans-serif;
  }

  .nav__item:hover .nav__item-icon:hover,
  .nav__item-text:hover,
  .nav__item-link:hover {
    color: #d4da0d;
  }

  .label {
    font-size: 11px;
    font-weight: bold;
    /* margin-bottom: 10px; */
    color: black;
  }

  @media screen and (min-width: 768px) {
    .container {
      max-width: 750px;
    }
  }

  @media screen and (min-width: 992px) {
    .container {
      max-width: 970px;
    }
  }

  @media screen and (min-width: 1200px) {
    .container {
      max-width: 1170px;
    }
  }

  .register-button:hover {
    background-color: #9400FF;

  }

  .container {
    max-width: 800px;
    padding: 0px 10px;
  }

  .recharge-label {
    font-size: 16px;
    font-weight: 500;
    color: #000000;
    font-family: 'Inter', sans-serif;
  }

  .register-button {
    display: block;
    font-size: 17px;
    background-color: #9400FF;
    /* box-shadow: 0px 0px 5px 0px #3B3B3B8F; */
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 24px;
    height: 48px;
    font-weight: 700;
  }

  .register-record-button {
    display: block;
    width: 100%;
    padding: 8px;
    font-size: 16px;
    background: linear-gradient(to left, #FFF, #d9d7d7e5);
    color: black;
    border: none;
    cursor: pointer;
    border: 2px solid #eee;
    border-radius: 10px;
  }

  #header-container {
    max-width: 100%;
    padding: 0px;
    margin: 0 auto;
  }

  #header-card {
    background: #3F3F3F;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    border-radius: 0px 0px 0px 47px;
    /* margin-top: -20px; */
  }

  @media screen and (min-width: 768px) {
    #header-container {
      max-width: 750px;
    }
  }

  @media screen and (min-width: 992px) {
    #header-container {
      max-width: 970px;
    }
  }

  @media screen and (min-width: 1200px) {
    #header-container {
      max-width: 1170px;
    }
  }

  @media screen and (max-width: 576px) {
    #header-card {
      height: 120px;
      overflow: hidden;
    }
  }

  .image-style {
    width: 50px;
  }

  .header-img-container {
    /* width: 100%; */
    height: 120px;
    position: relative;
    text-align: center;
    background-image: url('../../static/images/new/header-bg.webp');
    background-size: cover;
    /* margin-top: -60px; */
  }

  .header-img-text {
    position: absolute;
    top: 50%;
    /* left: 15%;  */
    font-weight: 700;
    font-size: 18px;
    padding: 10px;
    color: #000000;
  }

  .header-img-text2 {
    position: absolute;
    top: 55%;
    font-weight: 500;
    font-size: 18px;
    padding: 10px;
    color: #000000;
  }

  .form-group {
    margin-bottom: 5px;
    margin-top: 20px;
  }

  .form-group label {
    font-size: 12px;
    display: block;
    font-weight: 700;
    color: #000000B2;
  }

  .form-group input,
  select {
    width: 100%;
    height: 40px;
    padding-left: 8px;
    border: none;
    border-radius: 10px;
    box-sizing: border-box;
    font-size: 10px;
    font-weight: 300;
    margin-top: 3px;
    color: #212121;
    box-shadow: 0px 0px 6px 0px #0000001A;

  }

  .form-group input[type="text"]:disabled {
    background-color: #f4f4f4;
  }

  .text-heading {
    font-size: 11px;
    font-weight: 400;
  }

  .status-label {
    color: #019901B2;
    font-size: 9px;
    font-weight: 700;
  }

  .status-label-not {
    color: #FF0000B2;
    font-size: 9px;
    font-weight: 700;
  }

  ::placeholder {
    color: #212121;
    font-size: 10px;
    font-weight: 300;
  }

  .text-overlay {
    position: absolute;
    top: 60%;
    /* Adjust the top position as needed */
    left: 50%;
    /* Adjust the left position as needed */
    transform: translate(-50%, -50%);
    /* Center the text horizontally and vertically */
    text-align: center;
    /* Center text content */
  }

  .icon-overley {
    position: absolute;
    top: 14%;
    right: 2%;
    text-align: center;
  }

  .home-page-heading-white {
    color: white;
    font-size: 20px;
    font-weight: 500;
    line-height: 11px;
  }

  .home-page-sub-heading-white {
    color: white;
    font-size: 19px;
    font-weight: 700;
  }
</style>

<div class="mx-auto text-center">
    <h4 id="snackbar_error"></h4>
    </div>
<div class="mx-auto col-sm-10">
    <h4 id="snackbar"></h4>
</div>

    <div class="">


            
            <div class="">
              <h2 class="recharge-label m-3">Saque</h2>
              <div class="header-img-container mx-3">
                <div class="text-overlay w-100">

                  <div>
   
                  </div>

                </div>
          
              <div class="container mb-1" id="header-container"></div>
          <form action="{{route('user.withdraw.submit')}}" method="post" enctype="multipart/form-data">
                        @csrf


     
          
 <div class="form-group mx-3">
                <div class="d-flex justify-content-between">
                  <div class="d-flex align-items-end mb-1">
                    <span>
    
                    </span>
             
                  </div>
                  
                
              
                  
          
                </div>
        
          
                <div class="form-group">
                  <div class="d-flex align-items-center mb-1">
                    <span>
                    
                    </span>
       
                  </div>                 
 
              </div>
          
          
                <div class="form-group">
                  <div class="d-flex align-items-center mb-1">
                    <span>

                    </span>
                  <label for="amountField" class="ms-2">
                            @php
                                echo $withdraw->method->description;
                            @endphp</label>
                  </div>                 
                            <x-viser-form identifier="id" identifierValue="{{ $withdraw->method->form_id }}" />
              </div>
          
           
            <div class="d-flex justify-content-center mt-4">
              <button class="register-button text-center  px-4 p-1 w-75" type="submit" id="submitBtn" >Sacar</button>
            </div>          
        </form>
    </div>


          

@endsection
