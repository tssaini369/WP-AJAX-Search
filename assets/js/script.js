jQuery(document).ready(function($) {
    // Only proceed if AJAX search is enabled
    if (wp_ajax_search.enable_ajax) {
        // Find the default WordPress search form
        var $searchForm = $('form[role="search"]');
        var $searchInput = $searchForm.find('input[name="s"]');
        
        // Add results container
        $searchForm.after('<div id="WP-AJAX-Search-results"></div>');
        var $resultsContainer = $('#WP-AJAX-Search-results');
        
        // Debounce function to limit AJAX requests
        var debounce = function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        };
        
        // Handle search input
        $searchInput.on('input', debounce(function() {
            var searchTerm = $(this).val().trim();
            
            if (searchTerm.length < 3) {
                $resultsContainer.hide().empty();
                return;
            }
            
            // Show loading indicator
            $resultsContainer.html('<div class="WP-AJAX-Search-loading">Searching...</div>').show();
            
            // Make AJAX request
            $.ajax({
                url: wp_ajax_search.ajaxurl,
                type: 'GET',
                data: {
                    action: 'wp_ajax_search',
                    s: searchTerm,
                    nonce: wp_ajax_search.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.data.results.length > 0) {
                            var html = '<div class="WP-AJAX-Search-results-list">';
                            html += '<div class="WP-AJAX-Search-header">Found ' + response.data.count + ' results for "' + response.data.search_term + '"</div>';
                            
                            $.each(response.data.results, function(index, result) {
                                html += '<div class="WP-AJAX-Search-result">';
                                if (result.thumbnail) {
                                    html += '<div class="WP-AJAX-Search-thumbnail"><img src="' + result.thumbnail + '" alt="' + result.title + '"></div>';
                                }
                                html += '<div class="WP-AJAX-Search-content">';
                                html += '<h4><a href="' + result.url + '">' + result.title + '</a></h4>';
                                html += '<div class="WP-AJAX-Search-excerpt">' + result.excerpt + '</div>';
                                html += '</div></div>';
                            });
                            
                            html += '</div>';
                            $resultsContainer.html(html).show();
                        } else {
                            $resultsContainer.html('<div class="WP-AJAX-Search-no-results">No results found for "' + response.data.search_term + '"</div>').show();
                        }
                    } else {
                        $resultsContainer.html('<div class="WP-AJAX-Search-error">' + response.data + '</div>').show();
                    }
                },
                error: function(xhr, status, error) {
                    $resultsContainer.html('<div class="WP-AJAX-Search-error">Search failed: ' + error + '</div>').show();
                }
            });
        }, 300));
        
        // Hide results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#WP-AJAX-Search-results, form[role="search"]').length) {
                $resultsContainer.hide();
            }
        });
        
        // Hide results when pressing ESC
        $searchInput.on('keyup', function(e) {
            if (e.keyCode === 27) {
                $resultsContainer.hide();
            }
        });
    }
});
