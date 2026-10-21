@if (session('success'))
    <div hidden data-swal-flash data-icon="success" data-title="Berhasil" data-message="{{ session('success') }}"></div>
@endif

@if ($errors->any())
    <div hidden data-swal-flash data-icon="error" data-title="Terdapat data yang perlu diperbaiki" data-message="• {{ implode("\n• ", $errors->all()) }}"></div>
@endif
