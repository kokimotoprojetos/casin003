@extends($activeTemplate.'layouts.frontend')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css?family=Rajdhani:300,400,500,600,700');
    @import url('https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i');
    @import url('https://fonts.googleapis.com/css?family=Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i');
    @import url('https://fonts.googleapis.com/css?family=Open+Sans:400,600,600i,700,700i,800,800i&display=swap');

    * {
        font-family: 'Inter', sans-serif;
    }

    body {
        background-color: white;
    }

    .bottom-navbar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: whitesmoke;
        padding: 10px;
        border-radius: 15px 15px 0px 0px;
    }

    .scrollable-div {
        max-height: 40vh;
        overflow-y: auto;
    }

    .badge-notification {
        position: relative;
        top: -8px;
        left: -47px;
        border: 1px solid black;
        border-radius: 50%;
        font-size: 9px;
    }

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
        background-color: rgba(31, 28, 28, 0.5);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        color: white;
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
        from { bottom: 50%; opacity: 0; }
        to { bottom: 50%; opacity: 1; }
    }

    @keyframes fadeout {
        from { bottom: 50%; opacity: 1; }
        to { bottom: 50%; opacity: 0; }
    }

    @media (min-width: 576px) and (max-width: 767px) {
        #snackbar_error { width: 60%; left: 20%; bottom: 70px; font-size: 12px; }
        #snackbar { width: 50%; left: 25%; bottom: 70px; font-size: 12px; }
    }

    @media (max-width: 575.99px) {
        #snackbar_error { width: 80%; margin-left: 5px; left: 10%; font-size: 12px; }
        #snackbar { width: 80%; margin-left: 5px; left: 10%; font-size: 12px; }
    }

    .image-container {
        display: flex;
        justify-content: space-between;
    }

    .image-container .image-item {
        position: relative;
        max-width: 100px;
        max-height: 100px;
        margin: 10px;
        cursor: pointer;
    }

    .image-container .image-item img {
        max-width: 100%;
        max-height: 100%;
    }

    .image-container .image-item .delete-button {
        position: absolute;
        top: -9px;
        background-color: transparent;
        color: black;
        border: none;
        border-radius: 50%;
        cursor: pointer;
    }

    .bank-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px;
        color: black;
    }

    .bank-name {
        font-size: 10px;
        font-weight: 700;
        color: #3F3F3F80;
    }

    .amount-name {
        font-size: 18px;
        background: linear-gradient(to left, #FFF, #d9d7d7e5);
        color: black;
        text-align: center;
        border-radius: 10px;
    }

    .upload-button {
        display: block;
        width: 100%;
        padding: 10px;
        font-size: 13.97px;
        background: #5370e5;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 0px 23px 25px 0px;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
    }

    .cancel-button {
        font-family: 'Inter', sans-serif;
        display: block;
        width: 100%;
        padding: 10px;
        font-size: 13.97px;
        background: white;
        color: #5A75E680;
        cursor: pointer;
        border-radius: 26px 0px 0px 25px;
        font-weight: 500;
        box-shadow: 0px 0px 2px 0px #00000040;
    }

    .container {
        max-width: 1200px;
        height: 100%;
    }

    input[type="file"]::file-selector-button {
        background: linear-gradient(to left, #FFF, #d9d7d7e5);
        border: 1px solid #bbb;
        padding: 0.5em;
        width: 60%;
        height: 4vh;
        color: black;
        margin: 5%;
        margin-left: 0%;
    }

    .account-sub-heading {
        font-size: 12px;
        font-weight: 800;
        font-family: 'Inter', sans-serif;
    }

    .custom-file-input {
        display: nones;
    }

    .btn-outlined.btn-primary {
        background-color: #419EFE;
        color: white;
        background-color: #eaeaf6;
        display: block;
        width: 130px;
        margin-bottom: 10px;
        font-size: 18px;
        font-family: 'Inter', sans-serif;
        text-align: center;
    }

    .btn-outlined.btn-primary:active, .btn-outlined.btn-positive:active, .btn-outlined.btn-negative:active {
        background-color: #419EFE;
        color: white;
    }

    .btn-block {
        color: white;
        background-color: #eaeaf6;
        display: block;
        width: 90px;
        height: 90px;
        margin-bottom: 10px;
        font-size: 18px;
        font-family: 'Inter', sans-serif, Helvetica, sans-serif;
        text-align: center;
        border: 3px dotted #cfcfee;
    }

    .preview-image {
        width: 100px;
        height: 100px;
    }

    ::placeholder {
        font-family: 'Inter', sans-serif;
        font-size: 9px;
        font-weight: 400;
        line-height: 11px;
        letter-spacing: 0em;
        text-align: left;
        color: #3f3f3fbd;
    }

    .navbar {
        position: fixed;
        top: 0;
        width: 100%;
        font-size: 15px;
        font-weight: 800;
        font-family: 'Inter', sans-serif;
        background-color: #419EFE;
        color: #fff;
        text-align: center;
        z-index: 100;
    }

    .content {
        margin-top: 60px;
        padding: 20px;
    }

    .content p {
        margin-bottom: 20px;
    }
</style>

<div class="mx-auto text-center">
    <h4 id="snackbar_error"></h4>
</div>
<div class="mx-auto col-sm-10">
    <h4 id="snackbar"></h4>
</div>

<div class="text-center navbar">
    <h3 class=" text-center text-white p-1 w-100" style="display: flex;
    justify-content: center; font-family: 'Inter', sans-serif;
    align-items: center;">Informacoes de Pagamento</h3>
</div>
<div class="text-center pb-2 pt-5 d-flex justify-content-center" style="margin-left:90px;">
    <div class="mt-4">
        <img src="{{asset ('core/img/onepay.webp')}}"  alt="Imagem do cabecalho" style="width: 65px;">
        <h3 style="font-size: 13px; font-weight: 800; font-family: 'Inter', sans-serif;">One Pay</h3>
    </div>
    <div class="h-100 align-self-center ps-3">
        <span style="margin-left:0%; border: 0.5px solid rgba(0, 0, 0, 0.4); border-radius: 5px; padding: 2px; color: #3F3F3F66; font-size: 9.86px;">
            <i class="fa-solid fa-hourglass-end"></i>
            <span id="countdown"></span>
        </span>
    </div>
</div>
<div class="text-center">
</div>

<div class="w-100 mb-1 container" style="height:4vh;">
    <input type="number" name="recharge_amount" required value="2000" hidden>
    <input name="change_number" value="14" type="number" hidden >
    <input type="text" name="payment_method" required value="onpay" hidden>
    <input type="submit" class="text-end mt-2" style="font-size: 11px; font-weight: 600; color: #FAB752; float: right; background: none; border: none;" value="Erro na conta, clique para alterar>>">
</form>
</div>
<div class="container">
    <div class="card mt-1 mb-2" style="border: none; border-radius: 0px 0px 8px 8px; box-shadow: 0px 0px 2px 0px #0000002E;">
        <div style="background-color: #419EFE;">
            <h5 class="text-white account-sub-heading p-2">Passo 1. Copie as informacoes de pagamento</h5>
        </div>
        <div class="bank-info" style=" border-bottom: 2.5px dotted #80808061;">
            <span class="bank-name" style="font-family: 'Inter', sans-serif;">Numero da Conta</span>
            <span class="bank-name referal_code" id="account_number"  onclick="copyToClipboard()" style="font-family: 'Inter', sans-serif;">
                @php echo  $data->gateway->description @endphp
                <img  src="{{asset ('core/img/copyIcon.webp')}}"  alt="Imagem do cabecalho" id="liveToastBtn" style="width: 10px; height: 10px;">
            </span>
        </div>
        <div class="bank-info">
            <span style="font-size: 14px; font-weight: 800; font-family: 'Inter', sans-serif;">Valor:</span>
            <span style="color: #F33533; font-size: 17px; font-weight: 700; font-family: 'Inter', sans-serif;" id="account_title">{{showAmount($data['final_amo']) .' '.$data['method_currency'] }}</span>
        </div>
    </div>

    <div class="card mt-1" style="border: 2.5px dotted #80808061; border-radius: 0px 0px 14px 14px;">
        <div style="background-color: #419EFE;">
            <h5 class="text-white account-sub-heading p-2">Passo 2. Transfira o valor que deseja recarregar para nos atraves da transferencia.</h5>
        </div>
        <div class="bank-info">
            <span style="font-size: 10px; font-weight: 400; color: #3F3F3F80; font-family: 'Inter', sans-serif;"><span style="color: red; font-size: 14px;">*</span>Por favor, copie seu [ID] apos o pagamento.</span>
        </div>
    </div>

    <div class="card mt-3" style="border: 2.5px dotted #80808061; ">
        <div class="card" style="border: 1px solid #80808075;">
            <div style="background-color: #419EFE;">
                <h5 class="text-white account-sub-heading p-2">Passo 3. Insira o ID e faca upload do comprovante de pagamento para concluir a recarga.</h5>
            </div>
        </div>

        <form action="{{ route('user.deposit.manual.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="image-uploader">
                <div class="m-3">
                    <x-viser-form identifier="id" identifierValue="{{ $gateway->form_id }}" />
                </div>
                <div class="d-flex mt-2 mb-2">
                </div>
                <div class="modal" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog  modal-dialog-centered">
                        <div class="modal-content mx-2 text-center" style="background-color: transparent; border: none;">
                            <div style="background-color: #000000a3; border: none; border-radius: 5px;" class="p-3 w-25 mx-auto">
                                <div>
                                    <img src="/static/images/new/preview.gif" style="width: 35px; " />
                                </div>
                                <h1 style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 12px; color: whitesmoke;">Enviando Imagem</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex position-absolute bottom-0 w-100">
                <a href="{{route ('user.home')}}" class="cancel-button btn" type="button" id="cancelPayment">Cancelar pedido</a>
                <input class="upload-button px-5" type="submit" value="Confirmar pagamento" >
            </div>
        </div>
    </div>

    <script>
        const imageInput = document.getElementById('imageInput');
        const imageContainer = document.getElementById('imageContainer');
        const addImageBtn = document.getElementById('addImageBtn');

        let currentImageIndex = 0;

        imageInput.addEventListener('change', function () {
            const files = this.files;

            for (let i = 0; i < files.length; i++) {
               if (currentImageIndex >= 2) {
                    addImageBtn.style.display = 'none';
                    break;
               }

                const file = files[i];

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const imageItem = document.createElement('div');
                        imageItem.classList.add('image-item');
                        imageItem.dataset.index = currentImageIndex;

                        const image = document.createElement('img');
                        image.src = e.target.result;
                        image.classList.add('preview-image');

                        const deleteButton = document.createElement('button');
                        deleteButton.classList.add('delete-button');
                        deleteButton.innerHTML = '&#x2715;';
                        deleteButton.addEventListener('click', deleteImage);

                        imageItem.appendChild(image);
                        imageItem.appendChild(deleteButton);
                        imageContainer.appendChild(imageItem);
                        currentImageIndex++;

                        if (currentImageIndex >= 2) {
                            addImageBtn.style.display = 'none';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            }
            const displayedImages = imageContainer.querySelectorAll('add-image-btn').length;

            if (displayedImages > 2) {
                addImageBtn.style.display = 'none';
            } else {
                addImageBtn.style.display = 'block';
            }
        });

        function deleteImage() {
            const index = this.parentElement.dataset.index;
            const imageItem = document.querySelector(`[data-index="${index}"]`);
            imageItem.remove();

            currentImageIndex--;

            imageInput.disabled = false;
            const addImageBtn = document.querySelector('.add-image-btn');

            if (currentImageIndex < 2) {
                addImageBtn.style.display = 'block';
            }
        }
    </script>

    <script>
        function copyAccountNumber2() {
            var accountNumber = document.querySelector('.referal-code').textContent;
            navigator.clipboard.writeText(accountNumber);
        }

        const toastTrigger = document.getElementById('liveToastBtn')
        const namesnackbar = document.getElementById("namesnackbar")
        var snackbar = document.getElementById("snackbar");

        if (toastTrigger) {
            toastTrigger.addEventListener('click', () => {
                snackbar.className = "show";
                snackbar.textContent = 'Copiado com Sucesso'
                setTimeout(function(){ snackbar.className = snackbar.className.replace("show", ""); }, 3000);
            })
        }
        if (namesnackbar) {
            namesnackbar.addEventListener('click', () => {
                snackbar.className = "show";
                snackbar.textContent = 'Copiado com Sucesso'
                setTimeout(function(){ snackbar.className = snackbar.className.replace("show", ""); }, 3000);
            })
        }
    </script>

    <script>
        function copyToClipboard() {
            const div = document.getElementById("account_number");
            const textarea = document.createElement("textarea");
            textarea.value = div.innerText;
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            document.execCommand("copy");
            document.body.removeChild(textarea);
        }

        function copyToClipboard2() {
            const account_title = document.getElementById("account_title");
            const textarea = document.createElement("textarea");
            textarea.value = account_title.innerText;
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            document.execCommand("copy");
            document.body.removeChild(textarea);
        }

        document.getElementById('rechargeForm').addEventListener('submit', function (event) {
            var submitButton = document.getElementById("submitBtn");
            submitButton.disabled = true;
        });
    </script>

    <script>
        const targetDatetime = new Date("2023-11-04T05:07:40.463692");

        function updateCountdown() {
            const now = new Date();
            const timeDifference = targetDatetime - now;

            if (timeDifference <= 0) {
                document.getElementById("countdown").innerHTML = "Tempo Esgotado!";
                document.getElementById("countdown").style.fontSize = "8px";
                document.getElementById('submitBtn').style.display = 'none';
                document.getElementById('submitBtn2').style.display = 'block';
            } else {
                const minutes = Math.floor(timeDifference / (1000 * 60));
                const seconds = Math.floor((timeDifference % (1000 * 60)) / 1000);
                document.getElementById("countdown").innerHTML = `${minutes}m ${seconds}s`;
            }
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();

        function timer_expired() {
            var snackbar = document.getElementById("snackbar");
            snackbar.className = "show";
            snackbar.textContent = 'Tempo Esgotado'
            setTimeout(function () {
                snackbar.className = snackbar.className.replace("show", "");
            }, 3000);
        }
    </script>

    <script>
        document.getElementById("imageInput").addEventListener("change", function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    const modal = document.getElementById("exampleModal");
                    modal.classList.add("show");
                    modal.style.display = "block";

                    setTimeout(function () {
                        modal.style.display = "none";
                    }, 1000);
                };

                reader.readAsDataURL(file);
            }
        });
    </script>

    <br><br><br>

    <script>
        var notification_num = document.getElementById('notification_num')

        function NotificationFunction() {
            $.ajax({
                url: '/admin_dashboard/view_notification/',
                type: 'GET',
                error: function (xhr, status, error) {
                    console.error('Erro ao chamar funcao:', error);
                }
            });
            notification_num.style.display = 'none'
        }

        $(document).ready(function () {
            $(document).on('click', '#pagination-plan a', function (event) {
                event.preventDefault();
                var pageUrl = $(this).attr('href');

                $.ajax({
                    url: pageUrl,
                    type: 'GET',
                    dataType: 'html',
                    success: function (data) {
                        var result = $('<div />').append(data);
                        var newContent = result.find('#plan-container').html();
                        var newPagination = result.find('#pagination-plan').html();

                        $('#plan-container').html(newContent);
                        $('#pagination-plan').html(newPagination);
                    },
                    error: function () {
                        alert('Erro ao buscar pagina.');
                    }
                });
            });
        });
    </script>

@endsection
