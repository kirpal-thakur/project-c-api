<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screenshot to Image</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>
</head>

<body id="Bodyhtml">
    <style>
        #capture {
            border: 1px solid red;
            height: 350px;
        }

        .court_position {
            position: relative;
        }

        img.ground_fix {
            position: absolute;
            left: 0;
            top: 0;
            height: 300px;
        }

        img.dot_posg {
            position: absolute;
            left: 0;
            top: 0;
            height: 300px;
        }

        #captureMain {
            width: 100%;
            height: auto;
            /* border: 1px solid red; */
            height: 350px;
        }
    </style>
    <div id="genrated_image_text"></div>
    <div id="captureMain" style="margin: 45px;">
        <div class="court_position">
            <img src="https://nodejs.cgc.ac.in/widget/images/Playground-image.png" class="ground_fix" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point1.png" class="pos_st dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point2.png" class="pos_cam dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point3.png" class="pos_lm dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point4.png" class="pos_cm dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point5.png" class="pos_rm dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point6.png" class="pos_cdm dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point7.png" class="pos_lwb dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point8.png" class="pos_lb dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point9.png" class="pos_rb dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point10.png" class="pos_rwb dot_posg" />
            <img src="https://nodejs.cgc.ac.in/widget/images/playground-point11.png" class="pos_gk dot_posg" />
        </div>
    </div>
    <script>
        // function loadImagesAndCapture() {
        //     var node = document.getElementById('captureMain');
        //     domtoimage.toPng(node)
        //         .then(function(dataUrl) {
        //             if (dataUrl == 'data:,') {
        //                 loadImagesAndCapture();
        //                 return false;
        //             }
        //             $.ajax({
        //                 url: '<?= base_url(); ?>get-positions-image?action=pdf_image', // Adjust the URL to your controller's method
        //                 type: 'POST',
        //                 data: {
        //                     meta_value: dataUrl
        //                 },
        //                 success: function(response) {
        //                     console.log('Server response:', response);
        //                 },
        //                 error: function(xhr, status, error) {
        //                     console.error('AJAX Error:', error);
        //                 }
        //             });
        //         })
        //         .catch(function(error) {
        //             console.error('oops, something went wrong!', error);
        //         });
        // }
        function loadImagesAndCapture(call_back = 0) {
            var node = document.getElementById('captureMain');

            // Wait for all images inside the node to load
            var images = node.getElementsByTagName('img');
            var loadedImages = 0;

            for (var i = 0; i < images.length; i++) {
                images[i].onload = function() {
                    loadedImages++;
                    if (loadedImages === images.length) {
                        // All images are loaded, now capture the element
                        domtoimage.toPng(node)
                            .then(function(dataUrl) {
                                if (dataUrl === 'data:,') {
                                    loadImagesAndCapture(); // Retry if the data URL is still empty
                                    call_back++;
                                    if(call_back > 10){
                                        window.location.reload(true);
                                    }
                                    return;
                                }

                                // Send the captured image to the server
                                $.ajax({
                                    url: '<?= base_url(); ?>get-positions-image?action=pdf_image',
                                    type: 'POST',
                                    data: {
                                        meta_value: dataUrl
                                    },
                                    success: function(response) {
                                        console.log('Server response:', response);
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('AJAX Error:', error);
                                    }
                                });
                            })
                            .catch(function(error) {
                                console.error('oops, something went wrong!', error);
                            });
                    }
                };
            }

            // Fallback: If no images, capture immediately
            if (images.length === 0) {
                domtoimage.toPng(node)
                    .then(function(dataUrl) {
                        // Handle the capture similarly as above
                    })
                    .catch(function(error) {
                        console.error('oops, something went wrong!', error);
                    });
            }
        }

        // loadImagesAndCapture();

        // $(document).ready(function() {/
        loadImagesAndCapture();
        // });
    </script>
</body>

</html>