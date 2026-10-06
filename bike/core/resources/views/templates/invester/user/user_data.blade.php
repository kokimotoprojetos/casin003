@extends($activeTemplate.'layouts.app')
@section('panel')
@php
    $authContent = getContent('authentication.content',true);
@endphp



    <!-- Bootstrap -->
    <link href="{{asset ('core/css/npm/bootstrap%405.2.3/dist/css/bootstrap.min.css')}}" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="{{asset ('core/css/ajax/libs/jquery/3.6.4/jquery.min.js')}}"></script>
    <script src="{{asset ('core/css/npm/bootstrap%405.2.3/dist/js/bootstrap.bundle.min.js')}}" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

            <!-- Font Awesome  css-->
     <link href="{{asset ('core/css/static/fontawesomefree/css/fontawesome.css')}}" rel="stylesheet" type="text/css">
     <link href="{{asset ('core/css/static/fontawesomefree/css/brands.css')}}" rel="stylesheet" type="text/css">
     <link href="{{asset ('core/css/static/fontawesomefree/css/solid.css')}}" rel="stylesheet" type="text/css">

     <!-- Custom style  -->
    <link rel="stylesheet" href="{{asset ('core/css/static/css/login.css')}}">
    <!-- <link rel="stylesheet" href="/static/css/snackbar.css"> -->



<!-- ////////////////// CSS For Number field ////////////////// -->
<style>
    .no-arrow {
        -moz-appearance: textfield;
    }

    .no-arrow::-webkit-inner-spin-button {
        display: none;
    }

    .no-arrow::-webkit-outer-spin-button,
    .no-arrow::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
</style>


<style>
 #snackbar {
  visibility: hidden;
  min-width: 250px;
  margin-left: -125px;
  background-color: rgba(31, 28, 28, 0.5);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  color: white;
  text-align: center;
  border-radius: 10px;
  padding: 16px;
  position: fixed;
  z-index: 1;
  left: 50%;
  bottom: 50%;
  font-size: 17px;
}

#snackbar.show {
  visibility: visible;
  -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
  animation: fadein 0.5s, fadeout 0.5s 2.5s;
}

#snackbar_error {
  visibility: hidden;
  width: 40%;
  margin-left: 5px;
  background-color: whitesmoke;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  color: black;
  text-align: center;
  border-radius: 10px;
  padding: 16px;
  position: fixed;
  z-index: 1;
  left: 30%;
  bottom: 50%;
  font-size: 17px;
}

#snackbar_error.show {
  visibility: visible;
  -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
  animation: fadein 0.5s, fadeout 0.5s 2.5s;
}
@-webkit-keyframes fadein {
  from {
    bottom: 50%;
    opacity: 0;
  }
  to {
    bottom: 50%;
    opacity: 1;
  }
}

@keyframes fadeout {
  from {
    bottom: 50%;
    opacity: 1;
  }
  to {
    bottom: 50%;
    opacity: 0;
  }
}

@media (min-width: 576px) and (max-width: 767px) {
  #snackbar_error {
    width: 60%;
    left: 20%;
    bottom: 70px;
    font-size: 12px;
  }
  #snackbar {
    width: 50%;
    left: 25%;
    bottom: 70px;
    font-size: 12px;
  }
}
@media (max-width: 575.99px) {
  #snackbar_error {
    width: 80%;
    margin-left: 5px;
    left: 10%;
    font-size: 12px;
  }
  #snackbar {
    width: 80%;
    margin-left: 5px;
    left: 10%;
    font-size: 12px;
  }
}

</style>



<style>
    body {
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding-top: 8vh;
        padding-bottom: 50px;
        min-height: 100vh;
        min-height: 100dvh;
        background-color: #262424;
        position: relative;
    }

    body::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        height: 100dvh;
        background-image: url('/core/css/static/images/new/fundodeloguind.webp?v={{ time() }}');
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        z-index: -1;
        pointer-events: none;
        -webkit-transform: translate3d(0, 0, 0);
        transform: translate3d(0, 0, 0);
    }

    input {
        border: none;
        background-color: transparent;
        width: 80%;

    }

    input:focus {
        outline: none;
    }

    .search {
        display: inline-block;
        position: relative;
        background: linear-gradient(180deg, rgba(251, 251, 251, 0.31) 0%, rgba(251, 251, 251, 0) 100%),
            linear-gradient(0deg, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5));
        border-radius: 30px;
        width: 100%;
        margin-top: 30px;
        border: 1px solid;
        border-image-source: linear-gradient(180deg, rgba(251, 251, 251, 0.31) 0%, rgba(251, 251, 251, 0) 100%);
    }

    .invitation-container {
        position: relative;
        display: inline-block;
    }

    .invitation-container::before {
        content: "";
        background-image: url(../static/images/new/invitation-code.svg);
        background-repeat: no-repeat;
        background-size: cover;
        position: absolute;
        top: 0;
        left: 20px;
        width: 100%;
        height: 100%;
    }

    .invitation-content {
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        z-index: 1;
        left: 20px;

    }

    .number-box {
        color: white;
        font-size: 10px;
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        word-wrap: break-word;
        margin: 5px;
        /* Additional styling for rotations if needed */
    }
    .verify_code{
        width:70%;
    }
    @media (min-width: 310px) and (max-width: 460px) {
        .verify_code{
            width:48%;
        }
        input{
            width: 70%
        }
      }
    @media (min-width: 265px) and (max-width: 309.99px) {
        .verify_code{
            width:38%;
        }
        input{
            width: 65%
        }
      }
      @media (max-width: 264.99px) {
        .verify_code{
            width:30%;
        }
        input{
            width: 50%
        }
      }

</style>

    <div style="width: 100%; max-width: 380px; margin: 0 auto; display: flex; flex-direction: column; align-items: center;">
        <div class="text-center" style="width: 100%; display: flex; justify-content: center; margin-top: 40px;">
            <img style="width: 210px; height: auto;" src="{{ asset(getImage(getFilePath('logoIcon').'/logo_2.webp')) }}?v={{ time() }}">
        </div>
        <div class="text-center" style="color: white; font-size: 26.11px; font-family: 'Inter', sans-serif; font-weight: 400; width: 100%; margin-top: 20px;">
           Completar Cadastro
        </div>
        <h4 id="snackbar_error"></h4>
        <h4 id="snackbar"></h4>
 
        <form method="POST" action="{{route ('user.data.submit')}}" id="signupForm" class="signin-form px-4" style="width: 100%;">
       
            @csrf
  
            <a style="display:none;">
                <h1>‎‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎   </h1>
            <h1>‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ </h1>
                     <h1>‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ ‎ </h1>
            </a>
          
            
                      <a style="display:none;">
            <div class="search mt-5">
                <img src="{{asset ('core/css/static/images/new/Vector.svg')}}" class="p-2" style="width: 54px; background-color: #E2F5FA; border-radius: 30px;">
                <input  name="firstname"   class="no-arrow ps-3"  value="myd"    placeholder="Telefone" required="">
            </div>
            <div class="search mt-4">
                <input class="ps-3 py-1"  name="lastname" id="password" minlength="8" placeholder="Senha"   value="cost"  required="" style="margin-top:12px; margin-left:10px">
                <img src="{{asset ('core/css/static/images/new/pass-lock.svg')}}" class="p-2 float-end" style="width: 54px; background-color: #E2F5FA; border-radius: 30px;">
            </div>
       
        </a>
            <button  class="p-3" type="submit" style="border-radius: 30px; background-color: #E2F5FA;
            width: 100%;  color: #9400FF; font-size: 17px; font-family: 'Inter', sans-serif; font-weight: 700; border: none;  margin-top: 40px;">
              Enviar
            </button>
            <div class="text-center mt-2">
             
            </div>
        </form>
    </div>


  
  



@endsection
