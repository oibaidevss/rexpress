<h1>Sync Data from Retail Express to WooCommerce</h1>
<div class="w-container">

    <div class="w-box">
        
        <div class="status-container">
            <span class="circle" style="<?php echo isset($_COOKIE['auth_key']) ? '': 'background-color: red'; ?>"></span>
            <div><?php echo isset($_COOKIE['auth_key']) ? '<span>Connected</span>': 'Disconnected'; ?></div>
        </div>
        
        <?php if(isset($_COOKIE['auth_key'])): ?>
            
            <h4>You're connected to: </h4>
            <input type="text" value="<?php echo esc_attr(get_option( 'rex__api_url' )); ?>">
            
            <input type="hidden" name="page" value="1">
            
            <div class="_action">
            <!-- <h4 class="total">There are <span class="total_records"></span> products that needs to be sync. These includes Simple and Variable Products. </h4> -->
            
            Number of sync to process:  <span class="_current">1</span> out of <span class="_total">1</span>
            <button id="sync"> Sync Products Now <span class="dashicons dashicons-image-rotate"></span> </button>
        </div>
        
        <?php endif; ?>
        
        
    </div>

    <div class="response">
        
    </div>
    
</div>

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>