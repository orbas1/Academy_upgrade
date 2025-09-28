<link rel="shortcut icon" type="image/png"
    href="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/doku/img/favicon.png" />

<link rel="stylesheet"
    href="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/doku/stylesheet/css/main.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css"
    integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>

<!-- Load Jokul Checkout JS script -->
<script src="https://sandbox.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js"></script>

<script src="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/doku/js/jquery-3.3.1.min.js"></script>
<!-- Popper and Bootstrap JS -->
<script src="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/doku/js/popper.min.js"></script>
<script src="https://cdn-doku.oss-ap-southeast-5.aliyuncs.com/doku-ui-framework/doku/js/bootstrap.min.js"></script>



{{-- Payment Button --}}
<form id="formRequestData" class="needs-validation" novalidate>
    <button class="btn btn-primary">Pay by Doku</button>
</form>

<script>
    $("#formRequestData").submit(function(e) {
        $.ajax({
            type: "POST",
            url: "{{ route('payment.doku_checkout', ['identifier' => 'doku']) }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')
            },
            success: function(result) {
                loadJokulCheckout(result.response.payment.url);
            },
            error: function(xhr, textStatus, error) {
                console.log(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Order Failed',
                    confirmButtonText: 'Close',
                })
            }
        });
        e.preventDefault();
        return false;
    });
</script>
