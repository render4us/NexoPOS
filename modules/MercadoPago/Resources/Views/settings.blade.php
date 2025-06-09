<form method="POST" action="{{ route('mercadopago.settings.save') }}">
    @csrf
    <label>{{ __('Access Token') }}</label>
    <input type="text" name="mercadopago_access_token" value="{{ $token ?? '' }}">

    <label>{{ __('Device ID') }}</label>
    <input type="text" name="mercadopago_device_id" value="{{ $device_id ?? '' }}">

    <button type="submit">{{ __('Save') }}</button>
</form>
