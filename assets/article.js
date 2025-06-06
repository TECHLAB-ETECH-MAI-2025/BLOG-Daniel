import $ from 'jquery';

$(document).ready(function() {
	// Système de "j'aime" en AJAX
	const $likeButton = $('.like-button');
	const articleId = $likeButton.data('article-id');
	
	$likeButton.on('click', function (e) {
        e.preventDefault();

        $.ajax({
            url: `/api/article/${articleId}/like`,
            method: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const $icon = $likeButton.find('i');

                    if (response.liked) {
                        $icon.removeClass('bi-heart').addClass('bi-heart-fill text-danger');
                    } else {
                        $icon.removeClass('bi-heart-fill text-danger').addClass('bi-heart');
                    }

                    $('#likes-count').text(response.likesCount);
                }
            },
            error: function () {
                alert("Erreur lors de l'envoi du like.");
            }
        });
    });

	// Fonction pour afficher des alertes
	function showAlert(type, message) {
		const $alert = $(`
			<div class="alert alert-${type} alert-dismissible fade show" role="alert">
				${message}
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		`);
	
		$('#alerts-container').append($alert);
	
		setTimeout(() => {
			$alert.alert('close');
		}, 5000);
	}
});