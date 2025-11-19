<?php
$header_data = heading_data();
?>
<div class="rfs-header-main-middle bg-white border-b border-gray-200">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-12 gap-4 items-center py-4">

            <!-- Logo -->
            <div class="col-span-12 md:col-span-3">
                <?php echo $header_data['logo_image'] ?? ''; ?>
            </div>

            <!-- Search Bar -->
            <div class="col-span-12 md:col-span-6">
                <div class="rfs-header-search-form">
                    <?php echo $header_data['search_form'] ?? ''; ?>
                </div>
            </div>

            <!-- Account & Cart -->
            <div class="col-span-12 md:col-span-3">
                <div class="flex items-center justify-end space-x-4">
                    <?php if (!empty($header_data['my_account_url'])): ?>
                        <a href="<?php echo esc_url($header_data['my_account_url']); ?>" class="text-gray-600 hover:text-primary-800 flex items-center space-x-2">
                            <i class="fa-regular fa-user fa-lg"></i>
                            <span class="text-sm">My Account</span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($header_data['cart_url'])): ?>
                        <a href="<?php echo esc_url($header_data['cart_url']); ?>" class="text-gray-600 hover:text-primary-800 flex items-center space-x-2">
                            <div class="relative">
                                <i class="fa-solid fa-basket-shopping fa-lg"></i>
                                <?php if ($header_data['cart_contents_count'] > 0): ?>
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                        <?php echo esc_html($header_data['cart_contents_count']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm">Basket</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>