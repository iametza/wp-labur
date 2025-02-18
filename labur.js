document.addEventListener("DOMContentLoaded", function(event) {
    document.getElementById('button_labur_get_url').addEventListener('click', () => {
        jQuery.ajax({
            type: 'POST',
            url: MyAjax.ajax_path,
            dataType: 'json',
            data: {
                postID: MyAjax.post_id,
                action: MyAjax.action
            },
            async: true
            
        }).done((data) => {
            if (data.success) {
                jQuery('#labur_shortened_url').val(data.data)
            } else {
                alert(data.data)
            }

        }).error(() => {
            alert(data)

        })
    });
});
