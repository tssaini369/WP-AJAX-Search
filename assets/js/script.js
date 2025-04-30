jQuery(document).ready(function($) {
    if (!wp_ajax_search.enable_ajax) return;

    var $searchForm = $('form[role="search"]');
    $searchForm.css('position', 'relative');
    var $searchInput = $searchForm.find('input[name="s"]');
    $searchForm.after('<div id="WP-AJAX-Search-results"></div>');
    var $resultsContainer = $('#WP-AJAX-Search-results');
    var currentPage = 1;
    var loading = false;
    var lastSearchTerm = '';

    function loadResults(page, append) {
        var searchTerm = $searchInput.val().trim();
        if (searchTerm.length < 3) {
            $resultsContainer.hide().empty();
            return;
        }
        loading = true;
        if (!append) $resultsContainer.html('<div class="WP-AJAX-Search-loading">Searching...</div>').show();
        $.ajax({
            url: wp_ajax_search.ajaxurl,
            type: 'GET',
            data: {
                action: 'wp_ajax_search',
                s: searchTerm,
                nonce: wp_ajax_search.nonce,
                page: page
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var html = '';
                    if (!append) {
                        html += '<div class="WP-AJAX-Search-results-list">';
                        html += '<div class="WP-AJAX-Search-header">Found ' + response.data.count + ' results for "' + response.data.search_term + '"</div>';
                    }
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
                    if (!append) html += '</div>';
                    if (wp_ajax_search.loading_method === 'pagination') {
                        html += '<div class="WP-AJAX-Search-pagination" style="text-align:center;margin:10px 0;">';
                        if (response.data.page > 1) {
                            html += '<button class="WP-AJAX-Search-prev">Previous</button> ';
                        }
                        if (response.data.page < response.data.max_num_pages) {
                            html += '<button class="WP-AJAX-Search-next">Next</button>';
                        }
                        html += '</div>';
                    }
                    if (append) {
                        $resultsContainer.find('.WP-AJAX-Search-results-list').append(html);
                    } else {
                        $resultsContainer.html(html).show();
                    }
                } else {
                    $resultsContainer.html('<div class="WP-AJAX-Search-no-results">No results found for "' + searchTerm + '"</div>').show();
                }
                loading = false;
            },
            error: function(xhr, status, error) {
                $resultsContainer.html('<div class="WP-AJAX-Search-error">Search failed: ' + error + '</div>').show();
                loading = false;
            }
        });
    }

    function debounce(func, wait, immediate) {
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
    }

    $searchInput.on('input', debounce(function() {
        currentPage = 1;
        lastSearchTerm = $searchInput.val().trim();
        loadResults(currentPage, false);
    }, 300));

    // Pagination button handlers
    $resultsContainer.on('click', '.WP-AJAX-Search-prev', function() {
        if (currentPage > 1) {
            currentPage--;
            loadResults(currentPage, false);
        }
    });
    $resultsContainer.on('click', '.WP-AJAX-Search-next', function() {
        currentPage++;
        loadResults(currentPage, false);
    });

    // Infinite scroll
    $resultsContainer.on('scroll', function() {
        if (wp_ajax_search.loading_method !== 'infinite') return;
        if (loading) return;
        var $list = $resultsContainer.find('.WP-AJAX-Search-results-list');
        if ($resultsContainer.scrollTop() + $resultsContainer.innerHeight() >= $list.height() - 20) {
            currentPage++;
            loadResults(currentPage, true);
        }
    });
});
