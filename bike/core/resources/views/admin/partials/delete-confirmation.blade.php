<script>
    document.querySelector('.delete_confirm'+{{$row->id}}).addEventListener('click', function (e){
        var form = document.querySelector('.delete_confirm'+{{$row->id}}).parentElement;
        e.preventDefault()
        Swal.fire({
            title: 'Tem certeza?',
            text: "Se você excluir isto, será apagado para sempre.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
                Swal.fire(
                    'Excluído!',
                    'Seu arquivo foi excluído.',
                    'success'
                )
            }
        })
    })
</script>
