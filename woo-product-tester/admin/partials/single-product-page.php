<div class="wrap wcpt-admin-container">
    <h1><span class="material-icons">build</span> Single Product Tester</h1>

    <p style="margin:0 0 15px; font-size:0.9rem;">
        Made by <strong>Kyle Altenderfer</strong> – <a href="https://altenderfer.io/" target="_blank">altenderfer.io</a>
    </p>

    <p>Search or type a Product ID below, then click <strong>Start Test</strong>.</p>

    <form id="wcpt-single-product-form">
        <label for="wcpt-product-search"><i class="material-icons">search</i> Product Name:</label>
        <input type="text" id="wcpt-product-search" name="wcpt-product-search" autocomplete="off"
               placeholder="Type product name..." class="wcpt-wide-field" />

        <small style="display:block; margin-bottom:10px;">Type 2 or more characters for results.</small>

        <label for="wcpt-product-id-manual"><i class="material-icons">vpn_key</i> Product ID:</label>
        <input type="text" id="wcpt-product-id-manual" name="wcpt-product-id-manual" placeholder="Or enter ID..." />

        <div class="wcpt-button-row">
            <button type="submit" class="button button-primary wcpt-full-btn">
                <i class="material-icons">play_arrow</i> Start Test
            </button>
            <button id="wcpt-download-log" class="button wcpt-full-btn">
                <i class="material-icons">download</i> Download Log
            </button>
            <button id="wcpt-download-csv" class="button wcpt-full-btn">
                <i class="material-icons">download</i> Download CSV
            </button>
        </div>
    </form>

    <div id="wcpt-test-results" class="wcpt-test-output"></div>
</div>
