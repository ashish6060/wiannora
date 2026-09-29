<?php

session_start();

$pageTitle = 'Products';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/db.php';


/*
|--------------------------------------------------------------------------
| DATABASE CHECK
|--------------------------------------------------------------------------
*/

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| ADMIN CHECK
|--------------------------------------------------------------------------
*/

$isAdmin = (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'admin'
);


/*
|--------------------------------------------------------------------------
| IMAGE DIRECTORY
|--------------------------------------------------------------------------
*/

$uploadDir = __DIR__ . '/assets/images/products/';

if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0755, true)) {
        die('Unable to create product image directory.');
    }
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| CREATE SLUG
|--------------------------------------------------------------------------
*/

function createSlug($text)
{
    $text = strtolower(trim($text));

    $text = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $text
    );

    $text = trim($text, '-');

    return $text ?: 'product';
}


/*
|--------------------------------------------------------------------------
| UNIQUE SLUG
|--------------------------------------------------------------------------
*/

function uniqueSlug(PDO $pdo, $slug, $excludeId = 0)
{
    $originalSlug = $slug;
    $counter = 1;

    while (true) {

        if ($excludeId > 0) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM products
                WHERE slug = :slug
                AND id != :id
                LIMIT 1
            ");

            $stmt->execute([
                ':slug' => $slug,
                ':id'   => $excludeId
            ]);

        } else {

            $stmt = $pdo->prepare("
                SELECT id
                FROM products
                WHERE slug = :slug
                LIMIT 1
            ");

            $stmt->execute([
                ':slug' => $slug
            ]);
        }

        if (!$stmt->fetch()) {
            return $slug;
        }

        $counter++;

        $slug =
            $originalSlug .
            '-' .
            $counter;
    }
}


/*
|--------------------------------------------------------------------------
| IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

function uploadProductImage($file, $uploadDir)
{
    if (
        !isset($file) ||
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }


    $maxFileSize = 5 * 1024 * 1024;


    if (
        !isset($file['size']) ||
        $file['size'] > $maxFileSize
    ) {
        return null;
    }


    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];


    $mimeType = mime_content_type(
        $file['tmp_name']
    );


    if (
        !isset(
            $allowedTypes[$mimeType]
        )
    ) {
        return null;
    }


    if (
        @getimagesize(
            $file['tmp_name']
        ) === false
    ) {
        return null;
    }


    $extension =
        $allowedTypes[$mimeType];


    $imageName =
        'product_' .
        time() .
        '_' .
        bin2hex(
            random_bytes(5)
        ) .
        '.' .
        $extension;


    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $uploadDir . $imageName
        )
    ) {
        return null;
    }


    return $imageName;
}


/*
|--------------------------------------------------------------------------
| ADMIN PRODUCT ACTIONS
|--------------------------------------------------------------------------
*/

if (
    $isAdmin &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | ADD PRODUCT
    |--------------------------------------------------------------------------
    */

    if ($action === 'add') {

        $name = trim(
            $_POST['name'] ?? ''
        );

        $category = trim(
            $_POST['category'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $price = (float)(
            $_POST['price'] ?? 0
        );


        $salePrice = null;


        if (
            isset($_POST['sale_price']) &&
            $_POST['sale_price'] !== ''
        ) {

            $salePrice =
                (float)$_POST['sale_price'];
        }


        $visualClass = trim(
            $_POST['visual_class'] ?? ''
        );

        $badge = trim(
            $_POST['badge'] ?? ''
        );

        $stock = max(
            0,
            (int)(
                $_POST['stock'] ?? 0
            )
        );


        $status =
            $_POST['status'] ?? 'active';


        $allowedCategories = [
            'lips',
            'face',
            'eyes'
        ];


        $allowedStatuses = [
            'active',
            'inactive',
            'archived'
        ];


        if (
            $name !== '' &&
            in_array(
                $category,
                $allowedCategories,
                true
            ) &&
            $price >= 0 &&
            (
                $salePrice === null ||
                $salePrice >= 0
            ) &&
            in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $slug = uniqueSlug(
                $pdo,
                createSlug($name)
            );


            $imageName = null;


            if (
                isset($_FILES['image'])
            ) {

                $imageName =
                    uploadProductImage(
                        $_FILES['image'],
                        $uploadDir
                    );
            }


            $stmt = $pdo->prepare("
                INSERT INTO products
                (
                    name,
                    slug,
                    category,
                    description,
                    price,
                    sale_price,
                    image,
                    visual_class,
                    badge,
                    stock,
                    status
                )
                VALUES
                (
                    :name,
                    :slug,
                    :category,
                    :description,
                    :price,
                    :sale_price,
                    :image,
                    :visual_class,
                    :badge,
                    :stock,
                    :status
                )
            ");


            $stmt->execute([
                ':name'         => $name,
                ':slug'         => $slug,
                ':category'     => $category,
                ':description'  => $description,
                ':price'        => $price,
                ':sale_price'   => $salePrice,
                ':image'        => $imageName,
                ':visual_class' => $visualClass,
                ':badge'        => $badge,
                ':stock'        => $stock,
                ':status'       => $status
            ]);
        }


        header(
            'Location: products.php'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    if ($action === 'update') {

        $id = (int)(
            $_POST['id'] ?? 0
        );


        $name = trim(
            $_POST['name'] ?? ''
        );

        $category = trim(
            $_POST['category'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $price = (float)(
            $_POST['price'] ?? 0
        );


        $salePrice = null;


        if (
            isset($_POST['sale_price']) &&
            $_POST['sale_price'] !== ''
        ) {

            $salePrice =
                (float)$_POST['sale_price'];
        }


        $visualClass = trim(
            $_POST['visual_class'] ?? ''
        );

        $badge = trim(
            $_POST['badge'] ?? ''
        );

        $stock = max(
            0,
            (int)(
                $_POST['stock'] ?? 0
            )
        );


        $status =
            $_POST['status'] ?? 'active';


        $allowedCategories = [
            'lips',
            'face',
            'eyes'
        ];


        $allowedStatuses = [
            'active',
            'inactive',
            'archived'
        ];


        if (
            $id > 0 &&
            $name !== '' &&
            in_array(
                $category,
                $allowedCategories,
                true
            ) &&
            $price >= 0 &&
            (
                $salePrice === null ||
                $salePrice >= 0
            ) &&
            in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $stmt = $pdo->prepare("
                SELECT *
                FROM products
                WHERE id = :id
                LIMIT 1
            ");


            $stmt->execute([
                ':id' => $id
            ]);


            $currentProduct =
                $stmt->fetch();


            if ($currentProduct) {

                $oldImage =
                    $currentProduct['image'];


                $imageName =
                    $oldImage;


                if (
                    isset($_FILES['image']) &&
                    $_FILES['image']['error'] === UPLOAD_ERR_OK
                ) {

                    $newImage =
                        uploadProductImage(
                            $_FILES['image'],
                            $uploadDir
                        );


                    if ($newImage !== null) {

                        $imageName =
                            $newImage;


                        if (
                            !empty($oldImage) &&
                            file_exists(
                                $uploadDir .
                                $oldImage
                            )
                        ) {

                            unlink(
                                $uploadDir .
                                $oldImage
                            );
                        }
                    }
                }


                $slug = uniqueSlug(
                    $pdo,
                    createSlug($name),
                    $id
                );


                $stmt = $pdo->prepare("
                    UPDATE products
                    SET
                        name = :name,
                        slug = :slug,
                        category = :category,
                        description = :description,
                        price = :price,
                        sale_price = :sale_price,
                        image = :image,
                        visual_class = :visual_class,
                        badge = :badge,
                        stock = :stock,
                        status = :status
                    WHERE id = :id
                ");


                $stmt->execute([
                    ':name'         => $name,
                    ':slug'         => $slug,
                    ':category'     => $category,
                    ':description'  => $description,
                    ':price'        => $price,
                    ':sale_price'   => $salePrice,
                    ':image'        => $imageName,
                    ':visual_class' => $visualClass,
                    ':badge'        => $badge,
                    ':stock'        => $stock,
                    ':status'       => $status,
                    ':id'           => $id
                ]);
            }
        }


        header(
            'Location: products.php'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id = (int)(
            $_POST['id'] ?? 0
        );


        if ($id > 0) {

            $stmt = $pdo->prepare("
                SELECT image
                FROM products
                WHERE id = :id
                LIMIT 1
            ");


            $stmt->execute([
                ':id' => $id
            ]);


            $product =
                $stmt->fetch();


            $stmt = $pdo->prepare("
                DELETE FROM products
                WHERE id = :id
            ");


            $stmt->execute([
                ':id' => $id
            ]);


            if (
                $product &&
                !empty($product['image']) &&
                file_exists(
                    $uploadDir .
                    $product['image']
                )
            ) {

                unlink(
                    $uploadDir .
                    $product['image']
                );
            }
        }


        header(
            'Location: products.php'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD PRODUCTS
|--------------------------------------------------------------------------
*/

if ($isAdmin) {

    $stmt = $pdo->query("
        SELECT *
        FROM products
        ORDER BY created_at DESC
    ");

} else {

    $stmt = $pdo->query("
        SELECT *
        FROM products
        WHERE status = 'active'
        ORDER BY created_at DESC
    ");
}


$products =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include 'includes/header.php';

?>


<!-- =========================================================
     CART POPUP
     This works with the cart button in header.php
========================================================= -->

<div
    class="cart-overlay"
    data-cart-overlay
    aria-hidden="true"
></div>


<aside
    class="cart-popup"
    data-cart-popup
    aria-hidden="true"
>

    <!-- CART HEADER -->

    <div class="cart-header">

        <div>

            <span class="cart-eyebrow">
                YOUR BAG
            </span>

            <h2>
                Shopping Bag
            </h2>

        </div>


        <button
            type="button"
            class="cart-close"
            data-cart-close
            aria-label="Close shopping bag"
        >
            ×
        </button>

    </div>


    <!-- CART ITEMS -->

    <div
        class="cart-items"
        data-cart-items
    ></div>


    <!-- CART FOOTER -->

    <div class="cart-footer">

        <div class="cart-total-row">

            <span>
                Subtotal
            </span>

            <strong data-cart-total>
                ₹0.00
            </strong>

        </div>


        <button
            type="button"
            class="cart-checkout-btn"
            data-cart-checkout
        >
            Checkout
        </button>


        <a
            href="cart.php"
            class="cart-view-btn"
        >
            View Full Cart
        </a>

    </div>

</aside>


<!-- =========================================================
     PAGE HERO
========================================================= -->

<section class="page-hero">

    <div class="container">

        <span class="eyebrow">
            THE BEAUTY EDIT
        </span>

        <h1>
            Find your new <em>favourite.</em>
        </h1>

        <p>
            Colour, glow and definition for every mood.
        </p>

    </div>

</section>


<!-- =========================================================
     ADMIN PRODUCT MANAGEMENT
========================================================= -->

<?php if ($isAdmin): ?>

<section class="admin-product-panel">

    <div class="container admin-product-header">

        <div>

            <span class="eyebrow">
                ADMIN
            </span>

            <h2>
                Product Management
            </h2>

            <p>
                Manage your products directly from this page.
            </p>

        </div>


        <button
            type="button"
            class="admin-add-btn"
            onclick="openAddProduct()"
        >
            + Add Product
        </button>

    </div>

</section>

<?php endif; ?>


<!-- =========================================================
     SHOP
========================================================= -->

<section class="section container shop-section">

    <div class="shop-toolbar">

        <div
            class="filters"
            role="tablist"
        >

            <button
                class="filter active"
                data-filter="all"
            >
                All
            </button>


            <button
                class="filter"
                data-filter="lips"
            >
                Lips
            </button>


            <button
                class="filter"
                data-filter="face"
            >
                Face
            </button>


            <button
                class="filter"
                data-filter="eyes"
            >
                Eyes
            </button>

        </div>


        <select
            id="sortProducts"
            aria-label="Sort products"
        >

            <option value="featured">
                Sort: Featured
            </option>

            <option value="low">
                Price: Low to high
            </option>

            <option value="high">
                Price: High to low
            </option>

        </select>

    </div>


    <!-- =====================================================
         PRODUCT GRID
    ===================================================== -->

    <div
        class="product-grid shop-grid"
        id="productGrid"
    >

        <?php if (empty($products)): ?>

            <div class="no-products">

                <h3>
                    No products available
                </h3>

                <p>

                    <?php if ($isAdmin): ?>

                        Add your first product using
                        the button above.

                    <?php else: ?>

                        Please check back soon.

                    <?php endif; ?>

                </p>

            </div>

        <?php endif; ?>


        <?php foreach ($products as $product): ?>

        <article
            class="product-card"
            data-category="<?= e($product['category']) ?>"
            data-name="<?= e($product['name']) ?>"
            data-price="<?= e($product['price']) ?>"
            data-product-id="<?= (int)$product['id'] ?>"
            data-product-image="<?= e($product['image']) ?>"
            data-product-description="<?= e($product['description']) ?>"
            data-product-sale-price="<?= e($product['sale_price']) ?>"
            data-product-stock="<?= (int)$product['stock'] ?>"
            data-product-badge="<?= e($product['badge']) ?>"
            onclick="openProductDetails(this)"
        >

            <!-- PRODUCT VISUAL -->

            <div
                class="product-visual <?= e($product['visual_class']) ?>"
            >

                <?php if (!empty($product['badge'])): ?>

                    <span>
                        <?= e($product['badge']) ?>
                    </span>

                <?php endif; ?>


                <?php if (!empty($product['image'])): ?>

                    <img
                        src="assets/images/products/<?= e($product['image']) ?>"
                        alt="<?= e($product['name']) ?>"
                        class="real-product-image"
                    >

                <?php else: ?>

                    <div class="product-art">

                        <i></i>
                        <b></b>

                    </div>

                <?php endif; ?>

            </div>


            <!-- PRODUCT INFO -->

            <div class="product-info">

                <small>
                    <?= strtoupper(
                        e($product['category'])
                    ) ?>
                </small>


                <h3>
                    <?= e($product['name']) ?>
                </h3>


                <?php if (!empty($product['description'])): ?>

                    <p class="product-description">
                        <?= e(
                            $product['description']
                        ) ?>
                    </p>

                <?php endif; ?>


                <!-- PRICE -->

                <p>

                    <?php if (
                        $product['sale_price'] !== null &&
                        (float)$product['sale_price'] > 0 &&
                        (float)$product['sale_price'] <
                        (float)$product['price']
                    ): ?>

                        <span class="old-price">
                            ₹<?= number_format(
                                (float)$product['price'],
                                2
                            ) ?>
                        </span>


                        <span class="sale-price">
                            ₹<?= number_format(
                                (float)$product['sale_price'],
                                2
                            ) ?>
                        </span>

                    <?php else: ?>

                        ₹<?= number_format(
                            (float)$product['price'],
                            2
                        ) ?>

                    <?php endif; ?>

                </p>


                <!-- STOCK -->

                <?php if (
                    (int)$product['stock'] <= 0
                ): ?>

                    <small class="out-of-stock">
                        Out of stock
                    </small>

                <?php endif; ?>


                <!-- =================================================
                     MAIN CART CONTROL
                ================================================= -->

                <div
                    class="main-cart-control"
                    data-cart-control
                    data-product-id="<?= (int)$product['id'] ?>"
                    onclick="event.stopPropagation();"
                >

                    <?php if (
                        (int)$product['stock'] <= 0
                    ): ?>

                        <button
                            type="button"
                            class="add-btn"
                            disabled
                        >
                            Out of stock
                        </button>

                    <?php else: ?>

                        <!-- ADD TO CART -->

                        <button
                            type="button"
                            class="add-btn"
                            data-add
                            onclick="addProductToCart(this)"
                        >
                            Add to cart
                        </button>


                        <!-- QUANTITY -->

                        <div
                            class="main-quantity-control"
                            data-quantity-control
                        >

                            <button
                                type="button"
                                class="main-quantity-btn"
                                onclick="changeMainCartQuantity(
                                    <?= (int)$product['id'] ?>,
                                    -1
                                )"
                            >
                                −
                            </button>


                            <span
                                class="main-quantity-number"
                                data-main-quantity
                            >
                                0
                            </span>


                            <button
                                type="button"
                                class="main-quantity-btn"
                                onclick="changeMainCartQuantity(
                                    <?= (int)$product['id'] ?>,
                                    1
                                )"
                            >
                                +
                            </button>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- ADMIN ACTIONS -->

                <?php if ($isAdmin): ?>

                <div
                    class="admin-product-actions"
                    onclick="event.stopPropagation();"
                >

                    <button
                        type="button"
                        class="admin-edit-btn"
                        onclick='openEditProduct(
                            <?= json_encode(
                                $product,
                                JSON_HEX_TAG |
                                JSON_HEX_APOS |
                                JSON_HEX_QUOT |
                                JSON_HEX_AMP
                            ) ?>
                        )'
                    >
                        Edit
                    </button>


                    <form
                        method="POST"
                        onsubmit="return confirm('Delete this product permanently?');"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="delete"
                        >


                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$product['id'] ?>"
                        >


                        <button
                            type="submit"
                            class="admin-delete-btn"
                        >
                            Delete
                        </button>

                    </form>

                </div>

                <?php endif; ?>

            </div>

        </article>

        <?php endforeach; ?>

    </div>

</section>


<!-- =========================================================
     PRODUCT DETAILS MODAL
========================================================= -->

<div
    class="product-details-modal"
    id="productDetailsModal"
>

    <div
        class="product-details-overlay"
        onclick="closeProductDetails()"
    ></div>


    <div class="product-details-box">

        <button
            type="button"
            class="product-details-close"
            onclick="closeProductDetails()"
        >
            ×
        </button>


        <div class="product-details-content">

            <!-- IMAGE -->

            <div class="product-details-image">

                <img
                    id="detailsProductImage"
                    src=""
                    alt=""
                >


                <div
                    id="detailsProductArt"
                    class="product-art"
                >

                    <i></i>
                    <b></b>

                </div>

            </div>


            <!-- INFORMATION -->

            <div class="product-details-info">

                <span
                    class="product-details-category"
                    id="detailsProductCategory"
                ></span>


                <span
                    class="product-details-badge"
                    id="detailsProductBadge"
                ></span>


                <h2
                    id="detailsProductName"
                >
                    Product Name
                </h2>


                <p
                    class="product-details-description"
                    id="detailsProductDescription"
                ></p>


                <div
                    class="product-details-price"
                    id="detailsProductPrice"
                ></div>


                <p
                    class="product-details-stock"
                    id="detailsProductStock"
                ></p>


                <!-- QUANTITY -->

                <div class="quantity-section">

                    <span class="quantity-label">
                        Quantity
                    </span>


                    <div class="quantity-control">

                        <button
                            type="button"
                            class="quantity-btn"
                            id="quantityMinus"
                            onclick="changeProductQuantity(-1)"
                        >
                            −
                        </button>


                        <span
                            id="productQuantity"
                            class="quantity-number"
                        >
                            1
                        </span>


                        <button
                            type="button"
                            class="quantity-btn"
                            id="quantityPlus"
                            onclick="changeProductQuantity(1)"
                        >
                            +
                        </button>

                    </div>

                </div>


                <!-- ADD TO CART -->

                <button
                    type="button"
                    class="details-add-cart-btn"
                    id="detailsAddCartBtn"
                    onclick="addDetailsProductToCart()"
                >
                    Add to Cart
                </button>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     ADMIN MODAL
========================================================= -->

<?php if ($isAdmin): ?>

<div
    class="product-modal"
    id="productModal"
>

    <div
        class="product-modal-overlay"
        onclick="closeProductModal()"
    ></div>


    <div class="product-modal-box">

        <button
            type="button"
            class="modal-close"
            onclick="closeProductModal()"
        >
            ×
        </button>


        <h2 id="modalTitle">
            Add Product
        </h2>


        <p>
            Manage product information.
        </p>


        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="action"
                id="formAction"
                value="add"
            >


            <input
                type="hidden"
                name="id"
                id="productId"
            >


            <!-- NAME -->

            <div class="form-group">

                <label>
                    Product Name
                </label>


                <input
                    type="text"
                    name="name"
                    id="productName"
                    required
                >

            </div>


            <!-- CATEGORY -->

            <div class="form-group">

                <label>
                    Category
                </label>


                <select
                    name="category"
                    id="productCategory"
                    required
                >

                    <option value="lips">
                        Lips
                    </option>


                    <option value="face">
                        Face
                    </option>


                    <option value="eyes">
                        Eyes
                    </option>

                </select>

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label>
                    Description
                </label>


                <textarea
                    name="description"
                    id="productDescription"
                    rows="4"
                ></textarea>

            </div>


            <!-- PRICE -->

            <div class="form-row">

                <div class="form-group">

                    <label>
                        Price (₹)
                    </label>


                    <input
                        type="number"
                        name="price"
                        id="productPrice"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Sale Price (₹)
                    </label>


                    <input
                        type="number"
                        name="sale_price"
                        id="productSalePrice"
                        min="0"
                        step="0.01"
                    >

                </div>

            </div>


            <!-- STOCK -->

            <div class="form-group">

                <label>
                    Stock
                </label>


                <input
                    type="number"
                    name="stock"
                    id="productStock"
                    min="0"
                    value="0"
                    required
                >

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>
                    Status
                </label>


                <select
                    name="status"
                    id="productStatus"
                >

                    <option value="active">
                        Active
                    </option>


                    <option value="inactive">
                        Inactive
                    </option>


                    <option value="archived">
                        Archived
                    </option>

                </select>

            </div>


            <!-- VISUAL CLASS -->

            <div class="form-group">

                <label>
                    Visual Class
                </label>


                <input
                    type="text"
                    name="visual_class"
                    id="productVisualClass"
                    placeholder="visual-lip"
                >


                <small>
                    Example: visual-lip, visual-gloss,
                    visual-blush
                </small>

            </div>


            <!-- BADGE -->

            <div class="form-group">

                <label>
                    Badge
                </label>


                <input
                    type="text"
                    name="badge"
                    id="productBadge"
                    placeholder="NEW"
                >

            </div>


            <!-- IMAGE -->

            <div class="form-group">

                <label>
                    Product Image
                </label>


                <input
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                >


                <small>
                    JPG, PNG, WEBP or GIF
                </small>

            </div>


            <!-- SAVE -->

            <button
                type="submit"
                class="save-product-btn"
            >
                Save Product
            </button>

        </form>

    </div>

</div>

<?php endif; ?>


<?php include 'includes/footer.php'; ?>


<style>

/* =========================================================
   PRODUCT CARD
========================================================= */

.product-card {
    cursor: pointer;
}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

.real-product-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.product-description {
    font-size: 13px;
    color: #786a6b;
    line-height: 1.5;
}


/* =========================================================
   PRICE
========================================================= */

.old-price {
    text-decoration: line-through;
    color: #999;
    margin-right: 8px;
}


.sale-price {
    color: #a65b61;
    font-weight: 700;
}


/* =========================================================
   OUT OF STOCK
========================================================= */

.out-of-stock {
    display: block;
    margin-bottom: 8px;
    color: #a65b61;
    font-weight: 600;
}


/* =========================================================
   NO PRODUCTS
========================================================= */

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 70px 20px;
}


.no-products h3 {
    font-family: "Playfair Display", serif;
    font-size: 28px;
    margin-bottom: 10px;
}


.no-products p {
    color: #786a6b;
}


/* =========================================================
   MAIN CART CONTROL
========================================================= */

.main-cart-control {
    width: 100%;
    margin-top: 10px;
}


.main-cart-control .add-btn {
    width: 100%;
}


.main-quantity-control {
    width: 100%;

    display: none;

    align-items: center;
    justify-content: space-between;

    height: 44px;

    border: 1px solid #d9c4c0;
    border-radius: 8px;

    background: #fff;

    overflow: hidden;
}


.main-quantity-control.show {
    display: flex;
}


.main-quantity-btn {
    width: 44px;
    height: 44px;

    border: 0;

    background: #f8ebe7;

    color: #5f3035;

    font-size: 22px;
    font-weight: 600;

    cursor: pointer;

    transition: .2s ease;
}


.main-quantity-btn:hover {
    background: #f2d9d4;
}


.main-quantity-number {
    flex: 1;

    text-align: center;

    font-size: 15px;

    font-weight: 700;

    color: #21191a;
}


/* =========================================================
   PRODUCT DETAILS MODAL
========================================================= */

.product-details-modal {
    position: fixed;
    inset: 0;

    z-index: 10000;

    display: none;
}


.product-details-modal.show {
    display: flex;

    align-items: center;
    justify-content: center;
}


.product-details-overlay {
    position: absolute;
    inset: 0;

    background: rgba(33, 25, 26, .65);

    backdrop-filter: blur(3px);
}


.product-details-box {
    position: relative;

    z-index: 2;

    width: min(
        850px,
        calc(100% - 30px)
    );

    max-height: 90vh;

    overflow-y: auto;

    background: #fffaf8;

    border-radius: 18px;

    box-shadow:
        0 25px 80px
        rgba(33, 25, 26, .25);

    animation:
        productPopupIn .25s ease;
}


@keyframes productPopupIn {

    from {
        opacity: 0;
        transform:
            translateY(20px)
            scale(.97);
    }

    to {
        opacity: 1;
        transform:
            translateY(0)
            scale(1);
    }

}


.product-details-close {
    position: absolute;

    top: 15px;
    right: 18px;

    z-index: 5;

    width: 40px;
    height: 40px;

    border: 0;

    border-radius: 50%;

    background: rgba(
        255,
        255,
        255,
        .9
    );

    color: #5f3035;

    font-size: 28px;

    cursor: pointer;

    box-shadow:
        0 4px 15px
        rgba(0, 0, 0, .08);
}


.product-details-close:hover {
    background: #f2d9d4;
}


/* =========================================================
   MODAL CONTENT
========================================================= */

.product-details-content {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    min-height: 450px;
}


/* =========================================================
   MODAL IMAGE
========================================================= */

.product-details-image {
    position: relative;

    min-height: 450px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #f8ebe7;

    border-radius:
        18px 0 0 18px;

    overflow: hidden;
}


.product-details-image img {
    width: 100%;
    height: 100%;

    max-height: 450px;

    object-fit: contain;

    display: none;
}


.product-details-image img.show {
    display: block;
}


.product-details-image
.product-art {
    display: none;
}


.product-details-image
.product-art.show {
    display: block;
}


/* =========================================================
   MODAL INFORMATION
========================================================= */

.product-details-info {
    padding:
        55px
        45px
        45px;

    display: flex;

    flex-direction: column;

    justify-content: center;
}


.product-details-category {
    display: block;

    margin-bottom: 8px;

    color: #a65b61;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: 1.5px;
}


.product-details-badge {
    display: none;

    width: fit-content;

    padding:
        5px
        10px;

    margin-bottom: 12px;

    background: #f2d9d4;

    color: #5f3035;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;
}


.product-details-badge.show {
    display: inline-block;
}


.product-details-info h2 {
    margin:
        0
        0
        15px;

    font-family:
        "Playfair Display",
        serif;

    font-size: 36px;

    line-height: 1.15;

    color: #21191a;
}


.product-details-description {
    color: #786a6b;

    font-size: 15px;

    line-height: 1.7;

    margin:
        0
        0
        20px;
}


/* =========================================================
   MODAL PRICE
========================================================= */

.product-details-price {
    margin-bottom: 12px;

    font-size: 23px;

    font-weight: 700;

    color: #21191a;
}


.details-old-price {
    margin-right: 8px;

    color: #999;

    font-size: 16px;

    text-decoration:
        line-through;
}


.details-sale-price {
    color: #a65b61;

    font-size: 24px;
}


/* =========================================================
   STOCK
========================================================= */

.product-details-stock {
    margin:
        0
        0
        22px;

    color: #786a6b;

    font-size: 13px;
}


.product-details-stock.in-stock {
    color: #65805e;
}


.product-details-stock.out-stock {
    color: #a65b61;

    font-weight: 700;
}


/* =========================================================
   POPUP QUANTITY
========================================================= */

.quantity-section {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 20px;
}


.quantity-label {
    font-size: 14px;

    font-weight: 600;

    color: #21191a;
}


.quantity-control {
    display: flex;

    align-items: center;

    border:
        1px solid
        #dfcfcb;

    border-radius: 8px;

    overflow: hidden;

    background: #fff;
}


.quantity-btn {
    width: 42px;
    height: 42px;

    border: 0;

    background: #fff;

    color: #5f3035;

    font-size: 22px;

    cursor: pointer;
}


.quantity-btn:hover {
    background: #f8ebe7;
}


.quantity-btn:disabled {
    opacity: .4;

    cursor: not-allowed;
}


.quantity-number {
    width: 45px;

    text-align: center;

    font-size: 15px;

    font-weight: 700;

    color: #21191a;
}


/* =========================================================
   POPUP ADD CART
========================================================= */

.details-add-cart-btn {
    width: 100%;

    border: 0;

    padding:
        15px
        20px;

    border-radius: 8px;

    background: #a65b61;

    color: #fff;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    transition: .2s ease;
}


.details-add-cart-btn:hover {
    background: #7e3e43;
}


.details-add-cart-btn:disabled {
    background: #c8b8b6;

    cursor: not-allowed;
}


/* =========================================================
   CART MESSAGE
========================================================= */

.cart-added-message {
    position: fixed;

    right: 25px;
    bottom: 25px;

    z-index: 20000;

    padding:
        14px
        20px;

    background: #5f3035;

    color: #fff;

    border-radius: 8px;

    font-size: 14px;

    font-weight: 600;

    box-shadow:
        0 10px 30px
        rgba(0, 0, 0, .2);

    opacity: 0;

    visibility: hidden;

    transform:
        translateY(15px);

    transition: .25s ease;
}


.cart-added-message.show {
    opacity: 1;

    visibility: visible;

    transform:
        translateY(0);
}


/* =========================================================
   ADMIN
========================================================= */

<?php if ($isAdmin): ?>

.admin-product-panel {
    padding: 35px 0;

    background: #fff8f6;

    border-bottom:
        1px solid
        #eadbd7;
}


.admin-product-header {
    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 30px;
}


.admin-product-header h2 {
    margin:
        6px
        0;

    font-family:
        "Playfair Display",
        serif;

    font-size: 32px;
}


.admin-product-header p {
    color: #786a6b;
}


.admin-add-btn {
    border: 0;

    background: #a65b61;

    color: #fff;

    padding:
        13px
        22px;

    border-radius: 8px;

    cursor: pointer;

    font-weight: 600;
}


.admin-add-btn:hover {
    background: #7e3e43;
}


/* =========================================================
   ADMIN ACTIONS
========================================================= */

.admin-product-actions {
    display: flex;

    gap: 8px;

    margin-top: 12px;

    position: relative;

    z-index: 10;
}


.admin-product-actions button {
    border: 0;

    padding:
        8px
        13px;

    border-radius: 6px;

    cursor: pointer;

    font-size: 13px;

    font-weight: 600;
}


.admin-edit-btn {
    background: #f2d9d4;

    color: #5f3035;
}


.admin-delete-btn {
    background: #f7e1df;

    color: #9b3d3d;
}


.admin-edit-btn:hover {
    background: #e8c7c1;
}


.admin-delete-btn:hover {
    background: #f0caca;
}


/* =========================================================
   ADMIN MODAL
========================================================= */

.product-modal {
    position: fixed;

    inset: 0;

    z-index: 9999;

    display: none;
}


.product-modal.show {
    display: flex;

    align-items: center;

    justify-content: center;
}


.product-modal-overlay {
    position: absolute;

    inset: 0;

    background:
        rgba(
            33,
            25,
            26,
            .55
        );
}


.product-modal-box {
    position: relative;

    z-index: 2;

    width:
        min(
            650px,
            calc(100% - 30px)
        );

    max-height: 90vh;

    overflow-y: auto;

    background: #fff;

    border-radius: 14px;

    padding: 30px;

    box-shadow:
        0 20px 70px
        rgba(0, 0, 0, .2);
}


.product-modal-box h2 {
    font-family:
        "Playfair Display",
        serif;

    font-size: 30px;

    margin-bottom: 5px;
}


.product-modal-box > p {
    color: #786a6b;

    margin-bottom: 25px;
}


.modal-close {
    position: absolute;

    right: 18px;
    top: 15px;

    border: 0;

    background: transparent;

    font-size: 30px;

    cursor: pointer;

    color: #5f3035;
}


/* =========================================================
   ADMIN FORM
========================================================= */

.form-group {
    margin-bottom: 18px;
}


.form-row {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 15px;
}


.form-group label {
    display: block;

    margin-bottom: 7px;

    font-weight: 600;

    font-size: 14px;
}


.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;

    border:
        1px solid
        #e5d5d1;

    border-radius: 7px;

    padding: 12px;

    font-family: inherit;

    background: #fff;

    box-sizing: border-box;
}


.form-group textarea {
    resize: vertical;
}


.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;

    border-color: #a65b61;
}


.form-group small {
    display: block;

    margin-top: 5px;

    color: #786a6b;
}


.save-product-btn {
    width: 100%;

    border: 0;

    background: #a65b61;

    color: #fff;

    padding: 14px;

    border-radius: 8px;

    cursor: pointer;

    font-weight: 700;

    font-size: 15px;
}


.save-product-btn:hover {
    background: #7e3e43;
}

<?php endif; ?>


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .product-details-box {
        width:
            calc(100% - 20px);

        max-height: 94vh;

        border-radius: 14px;
    }


    .product-details-content {
        grid-template-columns: 1fr;
    }


    .product-details-image {
        min-height: 280px;

        max-height: 300px;

        border-radius:
            14px
            14px
            0
            0;
    }


    .product-details-image img {
        max-height: 280px;
    }


    .product-details-info {
        padding:
            30px
            22px
            25px;
    }


    .product-details-info h2 {
        font-size: 29px;
    }


    .quantity-section {
        margin-top: 5px;
    }


    .cart-added-message {
        left: 15px;

        right: 15px;

        bottom: 15px;

        text-align: center;
    }


    <?php if ($isAdmin): ?>

    .admin-product-header {
        flex-direction: column;

        align-items: flex-start;
    }


    .admin-add-btn {
        width: 100%;
    }


    .form-row {
        grid-template-columns: 1fr;
    }


    .product-modal-box {
        padding: 22px;
    }

    <?php endif; ?>

}

</style>


<script>

/*
|--------------------------------------------------------------------------
| WIANNORA CART
|--------------------------------------------------------------------------
| All cart data is stored in:
|
| localStorage key:
| wiannora_cart
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CART STORAGE KEY
|--------------------------------------------------------------------------
*/

const WIANNORA_CART_KEY =
    'wiannora_cart';


/*
|--------------------------------------------------------------------------
| GET CART
|--------------------------------------------------------------------------
*/

function getCart()
{
    try {

        const storedCart =
            localStorage.getItem(
                WIANNORA_CART_KEY
            );


        if (!storedCart) {
            return [];
        }


        const cart =
            JSON.parse(
                storedCart
            );


        return Array.isArray(cart)
            ? cart
            : [];

    } catch (error) {

        console.error(
            'Unable to read cart:',
            error
        );

        return [];
    }
}


/*
|--------------------------------------------------------------------------
| SAVE CART
|--------------------------------------------------------------------------
*/

function saveCart(cart)
{
    try {

        localStorage.setItem(
            WIANNORA_CART_KEY,
            JSON.stringify(cart)
        );

        window.dispatchEvent(
            new CustomEvent(
                'wiannora-cart-updated'
            )
        );

    } catch (error) {

        console.error(
            'Unable to save cart:',
            error
        );
    }
}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value)
{
    return String(
        value ?? ''
    )
    .replace(
        /&/g,
        '&amp;'
    )
    .replace(
        /</g,
        '&lt;'
    )
    .replace(
        />/g,
        '&gt;'
    )
    .replace(
        /"/g,
        '&quot;'
    )
    .replace(
        /'/g,
        '&#039;'
    );
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT PRICE
|--------------------------------------------------------------------------
*/

function getCartItemPrice(item)
{
    const salePrice =
        parseFloat(
            item.salePrice
        );


    const regularPrice =
        parseFloat(
            item.price
        ) || 0;


    if (
        !isNaN(salePrice) &&
        salePrice > 0 &&
        salePrice < regularPrice
    ) {

        return salePrice;
    }


    return regularPrice;
}


/*
|--------------------------------------------------------------------------
| GET CART TOTAL QUANTITY
|--------------------------------------------------------------------------
*/

function getCartTotalQuantity()
{
    const cart =
        getCart();


    return cart.reduce(
        function(total, item)
        {

            return total +
                (
                    parseInt(
                        item.quantity
                    ) || 0
                );

        },
        0
    );
}


/*
|--------------------------------------------------------------------------
| GET CART SUBTOTAL
|--------------------------------------------------------------------------
*/

function getCartSubtotal()
{
    const cart =
        getCart();


    return cart.reduce(
        function(total, item)
        {

            const price =
                getCartItemPrice(
                    item
                );


            const quantity =
                parseInt(
                    item.quantity
                ) || 0;


            return total +
                (
                    price *
                    quantity
                );

        },
        0
    );
}


/*
|--------------------------------------------------------------------------
| UPDATE CART COUNT
|--------------------------------------------------------------------------
*/

function updateCartCount()
{
    const total =
        getCartTotalQuantity();


    document
        .querySelectorAll(
            '[data-cart-count], .cart-count'
        )
        .forEach(
            function(element)
            {

                element.textContent =
                    total;


                if (
                    total <= 0
                ) {

                    element.classList.add(
                        'is-empty'
                    );

                } else {

                    element.classList.remove(
                        'is-empty'
                    );

                }

            }
        );


    updateMainProductControls();


    renderCartPopup();
}


/*
|--------------------------------------------------------------------------
| GET CART PRODUCT QUANTITY
|--------------------------------------------------------------------------
*/

function getCartProductQuantity(
    productId
)
{
    const cart =
        getCart();


    const item =
        cart.find(
            function(product)
            {

                return String(
                    product.id
                ) ===
                String(
                    productId
                );

            }
        );


    if (!item) {
        return 0;
    }


    return parseInt(
        item.quantity || 0
    );
}


/*
|--------------------------------------------------------------------------
| ADD TO CART STORAGE
|--------------------------------------------------------------------------
*/

function addToCartStorage(
    product,
    quantity
)
{
    const cart =
        getCart();


    const amount =
        Math.max(
            1,
            parseInt(
                quantity
            ) || 1
        );


    const existingIndex =
        cart.findIndex(
            function(item)
            {

                return String(
                    item.id
                ) ===
                String(
                    product.id
                );

            }
        );


    if (
        existingIndex !== -1
    ) {

        let newQuantity =
            (
                parseInt(
                    cart[
                        existingIndex
                    ].quantity
                ) || 0
            ) +
            amount;


        const stock =
            parseInt(
                product.stock
            ) || 0;


        if (
            stock > 0 &&
            newQuantity > stock
        ) {

            newQuantity =
                stock;
        }


        cart[
            existingIndex
        ].quantity =
            newQuantity;


        /*
        |----------------------------------------------------------------------
        | Update product information in case admin changed it
        |----------------------------------------------------------------------
        */

        cart[
            existingIndex
        ].name =
            product.name;


        cart[
            existingIndex
        ].price =
            product.price;


        cart[
            existingIndex
        ].salePrice =
            product.salePrice;


        cart[
            existingIndex
        ].stock =
            product.stock;


        cart[
            existingIndex
        ].image =
            product.image;


    } else {

        const stock =
            parseInt(
                product.stock
            ) || 0;


        const safeQuantity =
            stock > 0
                ? Math.min(
                    amount,
                    stock
                )
                : 0;


        if (
            safeQuantity <= 0
        ) {
            return;
        }


        cart.push({

            id:
                product.id,

            name:
                product.name,

            category:
                product.category,

            image:
                product.image,

            description:
                product.description,

            price:
                product.price,

            salePrice:
                product.salePrice,

            stock:
                product.stock,

            badge:
                product.badge || '',

            quantity:
                safeQuantity

        });

    }


    saveCart(
        cart
    );


    updateCartCount();
}


/*
|--------------------------------------------------------------------------
| MAIN PRODUCT QUANTITY
|--------------------------------------------------------------------------
*/

function changeMainCartQuantity(
    productId,
    amount
)
{
    const cart =
        getCart();


    const index =
        cart.findIndex(
            function(item)
            {

                return String(
                    item.id
                ) ===
                String(
                    productId
                );

            }
        );


    if (
        index === -1
    ) {

        return;
    }


    let quantity =
        parseInt(
            cart[
                index
            ].quantity
        ) || 0;


    quantity +=
        parseInt(
            amount
        ) || 0;


    if (
        quantity <= 0
    ) {

        cart.splice(
            index,
            1
        );

    } else {

        const stock =
            parseInt(
                cart[
                    index
                ].stock
            ) || 0;


        if (
            stock > 0 &&
            quantity > stock
        ) {

            quantity =
                stock;
        }


        cart[
            index
        ].quantity =
            quantity;
    }


    saveCart(
        cart
    );


    updateCartCount();
}


/*
|--------------------------------------------------------------------------
| UPDATE MAIN PRODUCT CONTROLS
|--------------------------------------------------------------------------
*/

function updateMainProductControls()
{
    const cart =
        getCart();


    document
        .querySelectorAll(
            '[data-cart-control]'
        )
        .forEach(
            function(control)
            {

                const productId =
                    String(
                        control.dataset.productId
                    );


                const cartItem =
                    cart.find(
                        function(item)
                        {

                            return String(
                                item.id
                            ) ===
                            productId;

                        }
                    );


                const addButton =
                    control.querySelector(
                        '[data-add]'
                    );


                const quantityControl =
                    control.querySelector(
                        '[data-quantity-control]'
                    );


                const quantityNumber =
                    control.querySelector(
                        '[data-main-quantity]'
                    );


                if (
                    cartItem &&
                    parseInt(
                        cartItem.quantity
                    ) > 0
                ) {

                    const quantity =
                        parseInt(
                            cartItem.quantity
                        );


                    if (addButton) {

                        addButton.style.display =
                            'none';
                    }


                    if (quantityControl) {

                        quantityControl.classList.add(
                            'show'
                        );
                    }


                    if (quantityNumber) {

                        quantityNumber.textContent =
                            quantity;
                    }


                } else {

                    if (addButton) {

                        addButton.style.display =
                            '';
                    }


                    if (quantityControl) {

                        quantityControl.classList.remove(
                            'show'
                        );
                    }


                    if (quantityNumber) {

                        quantityNumber.textContent =
                            '0';
                    }

                }

            }
        );
}


/*
|--------------------------------------------------------------------------
| ADD PRODUCT FROM CARD
|--------------------------------------------------------------------------
*/

function addProductToCart(
    button
)
{
    const card =
        button.closest(
            '.product-card'
        );


    if (!card) {
        return;
    }


    const product = {

        id:
            card.dataset.productId,

        name:
            card.dataset.name,

        category:
            card.dataset.category,

        image:
            card.dataset.productImage,

        description:
            card.dataset.productDescription,

        price:
            parseFloat(
                card.dataset.price
            ) || 0,

        salePrice:
            card.dataset.productSalePrice !== ''
                ? parseFloat(
                    card.dataset.productSalePrice
                )
                : null,

        stock:
            parseInt(
                card.dataset.productStock
            ) || 0,

        badge:
            card.dataset.productBadge || ''

    };


    if (
        product.stock <= 0
    ) {

        return;
    }


    addToCartStorage(
        product,
        1
    );


    showCartAddedMessage(
        product.name,
        1
    );


    openCart();
}


/*
|--------------------------------------------------------------------------
| RENDER CART POPUP
|--------------------------------------------------------------------------
*/

function renderCartPopup()
{
    const cartItemsElement =
        document.querySelector(
            '[data-cart-items]'
        );


    const totalElement =
        document.querySelector(
            '[data-cart-total]'
        );


    if (
        !cartItemsElement
    ) {

        return;
    }


    const cart =
        getCart();


    /*
    |----------------------------------------------------------------------
    | EMPTY CART
    |----------------------------------------------------------------------
    */

    if (
        cart.length === 0
    ) {

        cartItemsElement.innerHTML = `

            <div class="cart-empty">

                <div class="cart-empty-icon">
                    🛍
                </div>

                <h3>
                    Your bag is empty
                </h3>

                <p>
                    Add something beautiful to your bag.
                </p>

                <a
                    href="products.php"
                    class="cart-shop-btn"
                    onclick="closeCart()"
                >
                    Shop Now
                </a>

            </div>

        `;


        if (totalElement) {

            totalElement.textContent =
                '₹0.00';
        }


        updateCheckoutButton(
            true
        );


        return;
    }


    /*
    |----------------------------------------------------------------------
    | CART ITEMS
    |----------------------------------------------------------------------
    */

    let html = '';


    cart.forEach(
        function(item)
        {

            const price =
                getCartItemPrice(
                    item
                );


            const quantity =
                parseInt(
                    item.quantity
                ) || 0;


            const itemTotal =
                price *
                quantity;


            const stock =
                parseInt(
                    item.stock
                ) || 0;


            const image =
                item.image
                    ? (
                        'assets/images/products/' +
                        encodeURIComponent(
                            item.image
                        )
                    )
                    : '';


            const oldPrice =
                parseFloat(
                    item.price
                ) || 0;


            const hasSale =
                oldPrice > 0 &&
                price < oldPrice;


            html += `

                <div
                    class="cart-item"
                    data-cart-item-id="${escapeHtml(item.id)}"
                >

                    ${
                        image
                        ? `
                            <img
                                src="${image}"
                                alt="${escapeHtml(item.name)}"
                                class="cart-item-image"
                                onerror="this.style.display='none';"
                            >
                          `
                        : `
                            <div
                                class="cart-item-image"
                                aria-hidden="true"
                            ></div>
                          `
                    }


                    <div class="cart-item-info">

                        <h4>
                            ${escapeHtml(item.name)}
                        </h4>


                        <div class="cart-item-price">

                            ₹${price.toFixed(2)}

                            ${
                                hasSale
                                ? `
                                    <span class="cart-item-old-price">
                                        ₹${oldPrice.toFixed(2)}
                                    </span>
                                  `
                                : ''
                            }

                        </div>


                        <div class="cart-item-total">

                            Total:
                            ₹${itemTotal.toFixed(2)}

                        </div>


                        <div class="cart-item-quantity">

                            <button
                                type="button"
                                onclick="changePopupCartQuantity(
                                    '${escapeHtml(item.id)}',
                                    -1
                                )"
                                aria-label="Decrease quantity"
                            >
                                −
                            </button>


                            <span>
                                ${quantity}
                            </span>


                            <button
                                type="button"
                                ${
                                    stock > 0 &&
                                    quantity >= stock
                                        ? 'disabled'
                                        : ''
                                }
                                onclick="changePopupCartQuantity(
                                    '${escapeHtml(item.id)}',
                                    1
                                )"
                                aria-label="Increase quantity"
                            >
                                +
                            </button>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="cart-remove"
                        onclick="removePopupCartItem(
                            '${escapeHtml(item.id)}'
                        )"
                    >
                        Remove
                    </button>

                </div>

            `;

        }
    );


    cartItemsElement.innerHTML =
        html;


    if (totalElement) {

        totalElement.textContent =
            '₹' +
            getCartSubtotal().toFixed(2);
    }


    updateCheckoutButton(
        false
    );
}


/*
|--------------------------------------------------------------------------
| CHANGE POPUP CART QUANTITY
|--------------------------------------------------------------------------
*/

function changePopupCartQuantity(
    productId,
    amount
)
{
    const cart =
        getCart();


    const index =
        cart.findIndex(
            function(item)
            {

                return String(
                    item.id
                ) ===
                String(
                    productId
                );

            }
        );


    if (
        index === -1
    ) {

        return;
    }


    let quantity =
        parseInt(
            cart[
                index
            ].quantity
        ) || 0;


    quantity +=
        parseInt(
            amount
        ) || 0;


    const stock =
        parseInt(
            cart[
                index
            ].stock
        ) || 0;


    if (
        stock > 0 &&
        quantity > stock
    ) {

        quantity =
            stock;
    }


    if (
        quantity <= 0
    ) {

        cart.splice(
            index,
            1
        );

    } else {

        cart[
            index
        ].quantity =
            quantity;
    }


    saveCart(
        cart
    );


    updateCartCount();
}


/*
|--------------------------------------------------------------------------
| REMOVE CART ITEM
|--------------------------------------------------------------------------
*/

function removePopupCartItem(
    productId
)
{
    const cart =
        getCart();


    const updatedCart =
        cart.filter(
            function(item)
            {

                return String(
                    item.id
                ) !==
                String(
                    productId
                );

            }
        );


    saveCart(
        updatedCart
    );


    updateCartCount();
}


/*
|--------------------------------------------------------------------------
| OPEN CART
|--------------------------------------------------------------------------
*/

function openCart()
{
    const popup =
        document.querySelector(
            '[data-cart-popup]'
        );


    const overlay =
        document.querySelector(
            '[data-cart-overlay]'
        );


    if (!popup) {

        return;
    }


    renderCartPopup();


    popup.classList.add(
        'active'
    );


    if (overlay) {

        overlay.classList.add(
            'active'
        );

        overlay.setAttribute(
            'aria-hidden',
            'false'
        );
    }


    popup.setAttribute(
        'aria-hidden',
        'false'
    );


    document.body.classList.add(
        'cart-open'
    );
}


/*
|--------------------------------------------------------------------------
| CLOSE CART
|--------------------------------------------------------------------------
*/

function closeCart()
{
    const popup =
        document.querySelector(
            '[data-cart-popup]'
        );


    const overlay =
        document.querySelector(
            '[data-cart-overlay]'
        );


    if (popup) {

        popup.classList.remove(
            'active'
        );


        popup.setAttribute(
            'aria-hidden',
            'true'
        );
    }


    if (overlay) {

        overlay.classList.remove(
            'active'
        );


        overlay.setAttribute(
            'aria-hidden',
            'true'
        );
    }


    document.body.classList.remove(
        'cart-open'
    );
}


/*
|--------------------------------------------------------------------------
| CHECKOUT BUTTON
|--------------------------------------------------------------------------
*/

function updateCheckoutButton(
    empty
)
{
    const checkoutButton =
        document.querySelector(
            '[data-cart-checkout]'
        );


    if (!checkoutButton) {

        return;
    }


    checkoutButton.disabled =
        empty;
}


/*
|--------------------------------------------------------------------------
| CHECKOUT ACTION
|--------------------------------------------------------------------------
*/

function handleCartCheckout()
{
    const cart =
        getCart();


    if (
        cart.length === 0
    ) {

        return;
    }


    /*
    |----------------------------------------------------------------------
    | If checkout.php exists, go there.
    |----------------------------------------------------------------------
    */

    window.location.href =
        'checkout.php';
}


/*
|--------------------------------------------------------------------------
| PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

const productDetailsModal =
    document.getElementById(
        'productDetailsModal'
    );


let selectedProduct =
    null;


let selectedQuantity =
    1;


/*
|--------------------------------------------------------------------------
| OPEN PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

function openProductDetails(
    card
)
{
    if (!productDetailsModal) {

        return;
    }


    selectedProduct = {

        id:
            card.dataset.productId,

        name:
            card.dataset.name,

        category:
            card.dataset.category,

        image:
            card.dataset.productImage,

        description:
            card.dataset.productDescription,

        price:
            parseFloat(
                card.dataset.price
            ) || 0,

        salePrice:
            card.dataset.productSalePrice !== ''
                ? parseFloat(
                    card.dataset.productSalePrice
                )
                : null,

        stock:
            parseInt(
                card.dataset.productStock
            ) || 0,

        badge:
            card.dataset.productBadge || ''

    };


    selectedQuantity =
        getCartProductQuantity(
            selectedProduct.id
        );


    if (
        selectedQuantity <= 0
    ) {

        selectedQuantity =
            1;
    }


    document.getElementById(
        'detailsProductName'
    ).textContent =
        selectedProduct.name;


    document.getElementById(
        'detailsProductCategory'
    ).textContent =
        selectedProduct.category.toUpperCase();


    document.getElementById(
        'detailsProductDescription'
    ).textContent =
        selectedProduct.description ||
        'A beautiful addition to your beauty collection.';


    const badge =
        document.getElementById(
            'detailsProductBadge'
        );


    if (
        selectedProduct.badge
    ) {

        badge.textContent =
            selectedProduct.badge;


        badge.classList.add(
            'show'
        );

    } else {

        badge.textContent =
            '';


        badge.classList.remove(
            'show'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    const image =
        document.getElementById(
            'detailsProductImage'
        );


    const art =
        document.getElementById(
            'detailsProductArt'
        );


    if (
        selectedProduct.image
    ) {

        image.src =
            'assets/images/products/' +
            selectedProduct.image;


        image.alt =
            selectedProduct.name;


        image.classList.add(
            'show'
        );


        art.classList.remove(
            'show'
        );

    } else {

        image.src =
            '';


        image.classList.remove(
            'show'
        );


        art.classList.add(
            'show'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PRICE
    |--------------------------------------------------------------------------
    */

    const priceElement =
        document.getElementById(
            'detailsProductPrice'
        );


    if (
        selectedProduct.salePrice !== null &&
        selectedProduct.salePrice > 0 &&
        selectedProduct.salePrice <
        selectedProduct.price
    ) {

        priceElement.innerHTML =

            '<span class="details-old-price">' +

            '₹' +
            selectedProduct.price.toFixed(2) +

            '</span>' +

            '<span class="details-sale-price">' +

            '₹' +
            selectedProduct.salePrice.toFixed(2) +

            '</span>';

    } else {

        priceElement.textContent =
            '₹' +
            selectedProduct.price.toFixed(2);
    }


    updateProductStock();

    updateProductQuantity();


    productDetailsModal.classList.add(
        'show'
    );


    document.body.style.overflow =
        'hidden';
}


/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT STOCK
|--------------------------------------------------------------------------
*/

function updateProductStock()
{
    if (!selectedProduct) {

        return;
    }


    const stockElement =
        document.getElementById(
            'detailsProductStock'
        );


    const addButton =
        document.getElementById(
            'detailsAddCartBtn'
        );


    if (
        selectedProduct.stock <= 0
    ) {

        stockElement.textContent =
            'Out of stock';


        stockElement.className =
            'product-details-stock out-stock';


        addButton.disabled =
            true;


        addButton.textContent =
            'Out of stock';


        return;
    }


    stockElement.textContent =
        selectedProduct.stock +
        ' item' +
        (
            selectedProduct.stock === 1
                ? ''
                : 's'
        ) +
        ' available';


    stockElement.className =
        'product-details-stock in-stock';


    addButton.disabled =
        false;


    addButton.textContent =
        'Add to Cart';
}


/*
|--------------------------------------------------------------------------
| CHANGE PRODUCT DETAILS QUANTITY
|--------------------------------------------------------------------------
*/

function changeProductQuantity(
    amount
)
{
    if (!selectedProduct) {

        return;
    }


    let newQuantity =
        selectedQuantity +
        amount;


    if (
        newQuantity < 1
    ) {

        newQuantity =
            1;
    }


    if (
        selectedProduct.stock > 0 &&
        newQuantity >
        selectedProduct.stock
    ) {

        newQuantity =
            selectedProduct.stock;
    }


    selectedQuantity =
        newQuantity;


    updateProductQuantity();
}


/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT DETAILS QUANTITY
|--------------------------------------------------------------------------
*/

function updateProductQuantity()
{
    const quantityElement =
        document.getElementById(
            'productQuantity'
        );


    const minusButton =
        document.getElementById(
            'quantityMinus'
        );


    const plusButton =
        document.getElementById(
            'quantityPlus'
        );


    if (
        quantityElement
    ) {

        quantityElement.textContent =
            selectedQuantity;
    }


    if (
        minusButton
    ) {

        minusButton.disabled =
            selectedQuantity <= 1;
    }


    if (
        plusButton
    ) {

        plusButton.disabled =
            !selectedProduct ||
            selectedQuantity >=
            selectedProduct.stock;
    }
}


/*
|--------------------------------------------------------------------------
| ADD FROM PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

function addDetailsProductToCart()
{
    if (
        !selectedProduct ||
        selectedProduct.stock <= 0
    ) {

        return;
    }


    addToCartStorage(
        selectedProduct,
        selectedQuantity
    );


    showCartAddedMessage(
        selectedProduct.name,
        selectedQuantity
    );


    closeProductDetails();


    /*
    |--------------------------------------------------------------------------
    | Open shopping cart after adding
    |--------------------------------------------------------------------------
    */

    setTimeout(
        function()
        {

            openCart();

        },
        250
    );
}


/*
|--------------------------------------------------------------------------
| CART SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

function showCartAddedMessage(
    productName,
    quantity
)
{
    let message =
        document.getElementById(
            'cartAddedMessage'
        );


    if (!message) {

        message =
            document.createElement(
                'div'
            );


        message.id =
            'cartAddedMessage';


        message.className =
            'cart-added-message';


        document.body.appendChild(
            message
        );
    }


    message.textContent =
        quantity +
        ' × ' +
        productName +
        ' added to cart';


    message.classList.add(
        'show'
    );


    clearTimeout(
        window.cartMessageTimer
    );


    window.cartMessageTimer =
        setTimeout(
            function()
            {

                message.classList.remove(
                    'show'
                );

            },
            2500
        );
}


/*
|--------------------------------------------------------------------------
| CLOSE PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

function closeProductDetails()
{
    if (!productDetailsModal) {

        return;
    }


    productDetailsModal.classList.remove(
        'show'
    );


    /*
    |--------------------------------------------------------------------------
    | Don't restore body scrolling if cart is open
    |--------------------------------------------------------------------------
    */

    if (
        !document.body.classList.contains(
            'cart-open'
        )
    ) {

        document.body.style.overflow =
            '';
    }
}


/*
|--------------------------------------------------------------------------
| CART EVENT LISTENERS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function()
    {

        /*
        |--------------------------------------------------------------------------
        | Initial cart state
        |--------------------------------------------------------------------------
        */

        updateCartCount();

        renderCartPopup();


        /*
        |--------------------------------------------------------------------------
        | Open cart buttons
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '[data-cart-open]'
            )
            .forEach(
                function(button)
                {

                    button.addEventListener(
                        'click',
                        function(event)
                        {

                            event.preventDefault();

                            openCart();

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | Close cart buttons
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '[data-cart-close]'
            )
            .forEach(
                function(button)
                {

                    button.addEventListener(
                        'click',
                        function()
                        {

                            closeCart();

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | Cart overlay
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '[data-cart-overlay]'
            )
            .forEach(
                function(overlay)
                {

                    overlay.addEventListener(
                        'click',
                        function()
                        {

                            closeCart();

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '[data-cart-checkout]'
            )
            .forEach(
                function(button)
                {

                    button.addEventListener(
                        'click',
                        function()
                        {

                            handleCartCheckout();

                        }
                    );

                }
            );

    }
);


/*
|--------------------------------------------------------------------------
| ESCAPE KEY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function(event)
    {

        if (
            event.key !==
            'Escape'
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Close cart first
        |--------------------------------------------------------------------------
        */

        const cartPopup =
            document.querySelector(
                '[data-cart-popup]'
            );


        if (
            cartPopup &&
            cartPopup.classList.contains(
                'active'
            )
        ) {

            closeCart();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Close product details
        |--------------------------------------------------------------------------
        */

        if (
            productDetailsModal &&
            productDetailsModal.classList.contains(
                'show'
            )
        ) {

            closeProductDetails();

        }

    }
);


/*
|--------------------------------------------------------------------------
| STORAGE EVENT
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'storage',
    function(event)
    {

        if (
            event.key ===
            WIANNORA_CART_KEY
        ) {

            updateCartCount();

            renderCartPopup();
        }

    }
);


/*
|--------------------------------------------------------------------------
| CUSTOM CART UPDATE EVENT
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'wiannora-cart-updated',
    function()
    {

        updateCartCount();

        renderCartPopup();

    }
);

</script>


<?php if ($isAdmin): ?>

<script>

/*
|--------------------------------------------------------------------------
| ADMIN PRODUCT MODAL
|--------------------------------------------------------------------------
*/

const productModal =
    document.getElementById(
        'productModal'
    );


/*
|--------------------------------------------------------------------------
| ADD PRODUCT
|--------------------------------------------------------------------------
*/

function openAddProduct()
{
    document.getElementById(
        'modalTitle'
    ).textContent =
        'Add Product';


    document.getElementById(
        'formAction'
    ).value =
        'add';


    document.getElementById(
        'productId'
    ).value =
        '';


    document.getElementById(
        'productName'
    ).value =
        '';


    document.getElementById(
        'productCategory'
    ).value =
        'lips';


    document.getElementById(
        'productDescription'
    ).value =
        '';


    document.getElementById(
        'productPrice'
    ).value =
        '';


    document.getElementById(
        'productSalePrice'
    ).value =
        '';


    document.getElementById(
        'productStock'
    ).value =
        '0';


    document.getElementById(
        'productStatus'
    ).value =
        'active';


    document.getElementById(
        'productVisualClass'
    ).value =
        '';


    document.getElementById(
        'productBadge'
    ).value =
        '';


    productModal.classList.add(
        'show'
    );


    document.body.style.overflow =
        'hidden';
}


/*
|--------------------------------------------------------------------------
| EDIT PRODUCT
|--------------------------------------------------------------------------
*/

function openEditProduct(
    product
)
{
    document.getElementById(
        'modalTitle'
    ).textContent =
        'Edit Product';


    document.getElementById(
        'formAction'
    ).value =
        'update';


    document.getElementById(
        'productId'
    ).value =
        product.id;


    document.getElementById(
        'productName'
    ).value =
        product.name;


    document.getElementById(
        'productCategory'
    ).value =
        product.category;


    document.getElementById(
        'productDescription'
    ).value =
        product.description ||
        '';


    document.getElementById(
        'productPrice'
    ).value =
        product.price;


    document.getElementById(
        'productSalePrice'
    ).value =
        product.sale_price ?? '';


    document.getElementById(
        'productStock'
    ).value =
        product.stock;


    document.getElementById(
        'productStatus'
    ).value =
        product.status;


    document.getElementById(
        'productVisualClass'
    ).value =
        product.visual_class ||
        '';


    document.getElementById(
        'productBadge'
    ).value =
        product.badge ||
        '';


    productModal.classList.add(
        'show'
    );


    document.body.style.overflow =
        'hidden';
}


/*
|--------------------------------------------------------------------------
| CLOSE ADMIN MODAL
|--------------------------------------------------------------------------
*/

function closeProductModal()
{
    if (!productModal) {

        return;
    }


    productModal.classList.remove(
        'show'
    );


    /*
    |--------------------------------------------------------------------------
    | Keep body locked if cart is open
    |--------------------------------------------------------------------------
    */

    if (
        !document.body.classList.contains(
            'cart-open'
        )
    ) {

        document.body.style.overflow =
            '';
    }
}


/*
|--------------------------------------------------------------------------
| ADMIN ESCAPE
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function(event)
    {

        if (
            event.key === 'Escape' &&
            productModal &&
            productModal.classList.contains(
                'show'
            )
        ) {

            closeProductModal();

        }

    }
);

</script>

<?php endif; ?>