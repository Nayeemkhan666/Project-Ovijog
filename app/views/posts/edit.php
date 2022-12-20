<?php require APPROOT . '/views/inc/header.php'; ?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/post.css">

<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />

<style>
    .button-background-color {
        background-color: #293462;
        color: white;
    }

    .star-color {
        color: red;
    }

    .create-post-heading {
        background-color: #dae1e9;
        margin-top: 25px;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 55px;
    }

    .tagify {
        border: 1px solid black;
    }
</style>

<?php require_once APPROOT . '/views/inc/navbar.php'; ?>


<div class="container ">
    <div class="card create-post-heading">
        <div>
            <h3 class="text-center mb-0"><i class="fa-solid fa-pen"></i> Edit Post</h3>
        </div>
    </div>

    <div class="card card-body mt-4 mb-4 bg-light" ;>
        <form action=" <?php echo URLROOT; ?>/posts/edit/<?php echo $data['post_id']; ?>" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col">
                    <div class="form-group mb-4">
                        <label for="title" class="mb-2"><b>Title:</b> <sup class="star-color">*</sup></label>
                        <input type="text" name="title" class="form-control <?php echo (!empty($data['title_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['title']; ?>" style="border-color:black;">
                        <span class="invalid-feedback"><?php echo $data['title_err']; ?></span>
                    </div>
                    <div class="form-group mb-4">
                        <label for="exampleFormControlSelect1" class="mb-2"><b>Category:</b><sup class="star-color">*</sup></label>
                        <select name="category" class="form-control" id="categorySelect" style="border-color:black;">
                            <?php foreach ($data['categories'] as $category) : ?>
                                <option><?php echo $category->category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="formFileMultiple" class="form-label mb-2"><b>Photo:</b></label>
                        <input id="img-input" class="form-control" type="file" accept="image/png, image/jpeg" name="image" value="" style="border-color:black;">
                        <img id="img-show" width="20%" src="<?php echo $data['img_link']; ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label for="tags" class="mb-2"><b>Tags:</b></label>
                        <input class="form-control form-control-sm" name="tags" value="<?php echo $data['tags']; ?>" placeholder='Write some tags'>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="body" class="mb-2"><b>Body:</b> <sup class="star-color">*</sup></label>
                        <textarea name="body" class="form-control form-control-lg  <?php echo (!empty($data['body_err'])) ? 'is-invalid' : ''; ?>" rows="10" style="border-color:black;"><?php echo $data['body']; ?></textarea>
                        <span class="invalid-feedback"><?php echo $data['body_err']; ?></span>
                    </div>
                </div>
            </div>

            <div class="row mb-2 d-flex" style="justify-content: space-between;">
                <div class="col-lg-1 mt-1">
                    <a href="<?php echo URLROOT; ?>/posts" class="btn button-background-color">Back</a>
                </div>
                <div class="col-lg-1 mt-1">
                    <button type="submit" name="submit" class="btn button-background-color">Post</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $('#categorySelect').val('<?php echo $data['category']; ?>');

    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('#img-show').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#img-input").change(function() {
        readURL(this);
    });

    var inputElm = document.querySelector('input[name=tags]');
    var whitelist = [];

    // initialize Tagify on the above input node reference
    var tagify = new Tagify(inputElm, {
        originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
        whitelist: whitelist // Array of values
    })

    $.ajax({
        url: '<?php echo URLROOT; ?>/posts/getAllTags',
        type: 'post',
        dataType: 'json',
        success: function(s) {
            for (const sElement of s) {
                console.log(sElement['tag']);
                whitelist.push(sElement['tag']);
                tagify.whitelist.push(sElement['tag']);
            }
        },
        error: function(err) {
            console.log(err);
        }
    });
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>