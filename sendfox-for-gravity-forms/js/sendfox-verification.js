jQuery(document).ready(function($) {
    $('#verify-token').on('click', function(e) {
        e.preventDefault();

        var token = $('input[name="sendfox_api_key"]').val();

        if (!token) {
            $('#token-verification-result').html('<span style="color:red;">Please enter a token.</span>');
            return;
        }

        $.ajax({
            url: sendfoxVerification.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_sendfox_verify_token',
                token: token,
                _wpnonce: sendfoxVerification.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#token-verification-result').html('<span style="color:green;">' + response.data.message + '</span>');
                } else {
                    $('#token-verification-result').html('<span style="color:red;">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $('#token-verification-result').html('<span style="color:red;">Error verifying the token.</span>');
            }
        });
    });
});
