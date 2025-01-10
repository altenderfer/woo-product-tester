jQuery(document).ready(function($){

    // SINGLE PRODUCT
    const singleForm = $('#wcpt-single-product-form');
    const resultsDiv = $('#wcpt-test-results');
    const productSearchInput = document.querySelector('#wcpt-product-search');
    const manualIdInput = $('#wcpt-product-id-manual');

    // Awesomplete
    const awesomplete = new Awesomplete(productSearchInput, {
        minChars: 2,
        autoFirst: false
    });

    let debounceTimer = null;
    productSearchInput.addEventListener('input', function(){
        clearTimeout(debounceTimer);
        const val = this.value.trim();
        if (!val || val.length < 2) return;

        debounceTimer = setTimeout(() => {
            $.ajax({
                url: wcpt_params.ajax_url,
                method: 'GET',
                dataType: 'json',
                data: {
                    action: 'wcpt_search_products',
                    security: wcpt_params.nonce,
                    term: val
                },
                success: function(res){
                    if (Array.isArray(res)) {
                        awesomplete.list = res;
                    }
                },
                error: function(err){
                    console.error('Product search error:', err);
                }
            });
        }, 300);
    });

    awesomplete.replace = function(suggestion){
        productSearchInput.value = suggestion.label;
        manualIdInput.val(suggestion.value);
    };

    singleForm.on('submit', function(e){
        e.preventDefault();
        const pid = manualIdInput.val();
        if (!pid) {
            alert('Please enter a valid product ID or pick one from the search.');
            return;
        }
        resultsDiv.html('<p>Testing in progress...</p>');

        $.ajax({
            url: wcpt_params.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wcpt_test_single_product',
                security: wcpt_params.nonce,
                product_id: pid
            },
            success: function(resp){
                if (resp.success) {
                    resultsDiv.html(resp.data);
                } else {
                    resultsDiv.html('<p>Error: '+ resp.data +'</p>');
                }
            },
            error: function(xhr, status, error){
                console.error(error);
                resultsDiv.html('<p>AJAX error. Check console.</p>');
            }
        });
    });

    // Download log/CSV
    $('#wcpt-download-log').on('click', function(e){
        e.preventDefault();
        window.location.href = wcpt_params.log_url;
    });
    $('#wcpt-download-csv').on('click', function(e){
        e.preventDefault();
        window.location.href = wcpt_params.csv_url;
    });
});
