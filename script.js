jQuery(document).ready(function ($) {
    // Refresh Documentation Button
    $('#refresh-docs').on('click', function () {
        $('#loading-indicator').show();

        // Trigger the cron job via AJAX and immediately return
        $.post(
            themeDocsSettings.ajaxurl,
            {
                action: 'refresh_function_docs',
                _ajax_nonce: themeDocsSettings.nonce
            },
            function (response) {
                if (response.success) {
                    // Immediately start polling to check if the documentation is ready
                    setTimeout(checkDocumentationReady, 5000); // Wait 5 seconds before first check
                } else {
                    alert('Error scheduling documentation refresh.');
                    $('#loading-indicator').hide();
                }
            }
        ).fail(function (jqXHR) {
            console.log('AJAX Request Failed:', jqXHR.responseText);
            alert('There was an error scheduling the documentation refresh.');
            $('#loading-indicator').hide();
        });
    });

    // Function to poll for the documentation's completion
    function checkDocumentationReady() {
		$.post(
			themeDocsSettings.ajaxurl,
			{
				action: 'check_documentation_ready',
				_ajax_nonce: themeDocsSettings.nonce
			},
			function (response) {
				if (response.success && response.data.ready) {
					location.reload(); // Refresh the page if documentation is ready
				} else {
					// Retry after a delay if not ready
					setTimeout(checkDocumentationReady, 5000);
				}
			}
		).fail(function (jqXHR) {
			console.log('Polling Request Failed:', jqXHR.responseText);
		});
	}

    // Export Documentation to PDF Button
    $('#export-docs').on('click', function() {
        const content = document.getElementById('documentation-content');
        let markdownContent = "# Theme Documentation\n\n";

        // Iterate through each function documentation block and convert to markdown
        content.querySelectorAll('.function-doc').forEach((doc) => {
            const codeBlock = doc.querySelector('pre code').textContent.trim();
            const description = doc.querySelector('p').textContent.trim();
            
            markdownContent += "## Function\n";
            markdownContent += "```php\n" + codeBlock + "\n```\n\n";
            markdownContent += description + "\n\n";
        });

        // Convert to Blob and create a downloadable link
        const blob = new Blob([markdownContent], { type: "text/markdown" });
        const url = URL.createObjectURL(blob);

        // Create a temporary link to trigger the download
        const downloadLink = document.createElement("a");
        downloadLink.href = url;
        downloadLink.download = "theme-documentation.md";
        downloadLink.click();

        // Clean up the URL object
        URL.revokeObjectURL(url);
    });
});
