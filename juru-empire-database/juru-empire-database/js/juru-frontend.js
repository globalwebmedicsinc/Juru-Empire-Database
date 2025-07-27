jQuery(document).ready(function($) {
    console.log('Juru Empire Database: juru-frontend.js loaded');

    // Ensure dependencies are available
    if (typeof juruAjax === 'undefined') {
        console.error('Juru Empire Database: juruAjax object not defined. Ensure wp_localize_script is called.');
        return;
    }
    if (!wp || !wp.media) {
        console.error('Juru Empire Database: wp.media not available. Ensure wp_enqueue_media is called.');
        return;
    }
    if (!bootstrap || !bootstrap.Offcanvas) {
        console.error('Juru Empire Database: Bootstrap Offcanvas not available. Ensure Bootstrap JS is enqueued.');
        return;
    }

    // Initialize offcanvas
    var drawer = document.getElementById('juru-add-drawer');
    var offcanvasInstance = new bootstrap.Offcanvas(drawer);

    // Event delegation for add buttons
    $(document).on('click', '.juru-add-button', function(e) {
        e.preventDefault();
        console.log('Juru Empire Database: Add button clicked');
        var type = $(this).data('type');
        if (!type) {
            console.error('Juru Empire Database: No data-type attribute found on button');
            return;
        }
        console.log('Juru Empire Database: Opening drawer for type: ' + type);

        var drawerElement = $('#juru-add-drawer');
        if (!drawerElement.length) {
            console.error('Juru Empire Database: Drawer element #juru-add-drawer not found in DOM');
            return;
        }

        var formContainer = $('#juru-drawer-form');
        if (!formContainer.length) {
            console.error('Juru Empire Database: Form container #juru-drawer-form not found in DOM');
            return;
        }

        formContainer.empty();
        var formHtml = '<h2 class="mb-3">Add ' + type.charAt(0).toUpperCase() + type.slice(1) + '</h2>';
        formHtml += '<div class="juru-form">';
        formHtml += '<div class="form-group mb-3"><label for="juru-title" class="form-label">Title</label><input type="text" class="form-control" id="juru-title" required></div>';

        if (type === 'system') {
            formHtml += '<div class="form-group mb-3"><label for="juru-economy-type" class="form-label">Economy Type</label><input type="text" class="form-control" id="juru-economy-type"></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-economy-strength" class="form-label">Economy Strength</label><input type="text" class="form-control" id="juru-economy-strength"></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-orbital-bodies" class="form-label">Orbital Bodies (1-6)</label><input type="number" class="form-control" id="juru-orbital-bodies" min="1" max="6"></div>';
        } else if (type === 'planet') {
            formHtml += '<div class="form-group mb-3"><label for="juru-system" class="form-label">System</label><select class="form-select" id="juru-system"><option value="">Loading...</option></select></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-portal-address" class="form-label">Portal Address</label><input type="text" class="form-control" id="juru-portal-address"></div>';
            $.ajax({
                url: juruAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'juru_get_systems',
                    nonce: juruAjax.nonce
                },
                success: function(response) {
                    console.log('Juru Empire Database: Systems fetched successfully', response);
                    if (response.success) {
                        var options = '<option value="">Select a System</option>';
                        $.each(response.data, function(i, system) {
                            options += '<option value="' + system.id + '">' + system.title + '</option>';
                        });
                        $('#juru-system').html(options);
                    } else {
                        console.error('Juru Empire Database: Failed to load systems', response);
                        alert('Failed to load systems: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Juru Empire Database: Error fetching systems', status, error);
                    alert('Error fetching systems: ' + error);
                }
            });
        } else if (type === 'poi') {
            formHtml += '<div class="form-group mb-3"><label for="juru-planet" class="form-label">Planet</label><select class="form-select" id="juru-planet"><option value="">Loading...</option></select></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-poi-type" class="form-label">POI Type</label><select class="form-select" id="juru-poi-type">';
            formHtml += '<option value="ships">Ships</option>';
            formHtml += '<option value="trading_outpost">Trading Outpost</option>';
            formHtml += '<option value="archives_outpost">Archives Outpost</option>';
            formHtml += '<option value="minor_settlement">Minor Settlement</option>';
            formHtml += '<option value="crashed_freighter">Crashed Freighter</option>';
            formHtml += '<option value="settlements">Settlements</option>';
            formHtml += '</select></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-coord-x" class="form-label">X Coordinate</label><input type="number" class="form-control" id="juru-coord-x" step="0.01" min="0" max="' + juruAjax.max_coord_x + '"></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-coord-y" class="form-label">Y Coordinate</label><input type="number" class="form-control" id="juru-coord-y" step="0.01" min="0" max="' + juruAjax.max_coord_y + '"></div>';
            $.ajax({
                url: juruAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'juru_get_planets',
                    nonce: juruAjax.nonce
                },
                success: function(response) {
                    console.log('Juru Empire Database: Planets fetched successfully', response);
                    if (response.success) {
                        var options = '<option value="">Select a Planet</option>';
                        $.each(response.data, function(i, planet) {
                            options += '<option value="' + planet.id + '">' + planet.title + '</option>';
                        });
                        $('#juru-planet').html(options);
                    } else {
                        console.error('Juru Empire Database: Failed to load planets', response);
                        alert('Failed to load planets: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Juru Empire Database: Error fetching planets', status, error);
                    alert('Error fetching planets: ' + error);
                }
            });
        } else if (type === 'fauna') {
            formHtml += '<div class="form-group mb-3"><label for="juru-planet" class="form-label">Planet</label><select class="form-select" id="juru-planet"><option value="">Loading...</option></select></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-fauna-description" class="form-label">Description</label><textarea class="form-control" id="juru-fauna-description" rows="4"></textarea></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-fauna-diet" class="form-label">Diet</label><input type="text" class="form-control" id="juru-fauna-diet"></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-fauna-produces" class="form-label">Produces</label><input type="text" class="form-control" id="juru-fauna-produces"></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-fauna-image-1" class="form-label">Featured Image</label><input type="hidden" id="juru-fauna-image-1"><img id="juru-fauna-image-1-preview" class="img-thumbnail mt-2" style="max-width:150px;display:none;"><div><button type="button" class="btn btn-secondary juru-upload-image" data-target="juru-fauna-image-1">Select Image</button><button type="button" class="btn btn-outline-danger juru-remove-image" data-target="juru-fauna-image-1" style="display:none;">Remove Image</button></div></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-fauna-image-2" class="form-label">Additional Image 1</label><input type="hidden" id="juru-fauna-image-2"><img id="juru-fauna-image-2-preview" class="img-thumbnail mt-2" style="max-width:150px;display:none;"><div><button type="button" class="btn btn-secondary juru-upload-image" data-target="juru-fauna-image-2">Select Image</button><button type="button" class="btn btn-outline-danger juru-remove-image" data-target="juru-fauna-image-2" style="display:none;">Remove Image</button></div></div>';
            formHtml += '<div class="form-group mb-3"><label for="juru-fauna-image-3" class="form-label">Additional Image 2</label><input type="hidden" id="juru-fauna-image-3"><img id="juru-fauna-image-3-preview" class="img-thumbnail mt-2" style="max-width:150px;display:none;"><div><button type="button" class="btn btn-secondary juru-upload-image" data-target="juru-fauna-image-3">Select Image</button><button type="button" class="btn btn-outline-danger juru-remove-image" data-target="juru-fauna-image-3" style="display:none;">Remove Image</button></div></div>';
            $.ajax({
                url: juruAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'juru_get_planets',
                    nonce: juruAjax.nonce
                },
                success: function(response) {
                    console.log('Juru Empire Database: Planets fetched successfully for fauna', response);
                    if (response.success) {
                        var options = '<option value="">Select a Planet</option>';
                        $.each(response.data, function(i, planet) {
                            options += '<option value="' + planet.id + '">' + planet.title + '</option>';
                        });
                        $('#juru-planet').html(options);
                    } else {
                        console.error('Juru Empire Database: Failed to load planets for fauna', response);
                        alert('Failed to load planets: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Juru Empire Database: Error fetching planets for fauna', status, error);
                    alert('Error fetching planets: ' + error);
                }
            });
        }

        formHtml += '<div class="form-group mb-3"><button type="button" class="btn btn-primary" id="juru-submit-' + type + '">Submit</button></div>';
        formHtml += '</div>';
        formContainer.html(formHtml);
        offcanvasInstance.show();
        // Ensure focus is set to enable input
        $('#juru-title').focus();
        console.log('Juru Empire Database: Drawer opened for type: ' + type);
    });

    // Close drawer
    $(document).on('click', '#juru-drawer-close', function() {
        console.log('Juru Empire Database: Closing drawer');
        offcanvasInstance.hide();
    });

    // Image upload handler
    $(document).on('click', '.juru-upload-image', function() {
        var target = $(this).data('target');
        console.log('Juru Empire Database: Opening media uploader for target: ' + target);
        var frame = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            console.log('Juru Empire Database: Image selected, ID: ' + attachment.id);
            $('#' + target).val(attachment.id);
            $('#' + target + '-preview').attr('src', attachment.url).css('display', 'block');
            $('button[data-target="' + target + '"].juru-remove-image').css('display', 'inline-block');
        });
        frame.open();
    });

    // Image remove handler
    $(document).on('click', '.juru-remove-image', function() {
        var target = $(this).data('target');
        console.log('Juru Empire Database: Removing image for target: ' + target);
        $('#' + target).val('');
        $('#' + target + '-preview').css('display', 'none');
        $(this).css('display', 'none');
    });

    // Submit handler
    $(document).on('click', '[id^=juru-submit-]', function() {
        var type = $(this).attr('id').replace('juru-submit-', '');
        console.log('Juru Empire Database: Submitting form for type: ' + type);
        var data = {
            action: 'juru_add_' + type,
            nonce: juruAjax.nonce,
            title: $('#juru-title').val()
        };

        if (type === 'system') {
            data.economy_type = $('#juru-economy-type').val();
            data.economy_strength = $('#juru-economy-strength').val();
            data.orbital_bodies = $('#juru-orbital-bodies').val();
        } else if (type === 'planet') {
            data.system = $('#juru-system').val();
            data.portal_address = $('#juru-portal-address').val();
        } else if (type === 'poi') {
            data.planet = $('#juru-planet').val();
            data.poi_type = $('#juru-poi-type').val();
            data.juru_coord_x = $('#juru-coord-x').val();
            data.juru_coord_y = $('#juru-coord-y').val();
        } else if (type === 'fauna') {
            data.planet = $('#juru-planet').val();
            data.description = $('#juru-fauna-description').val();
            data.diet = $('#juru-fauna-diet').val();
            data.produces = $('#juru-fauna-produces').val();
            data.image_1 = $('#juru-fauna-image-1').val();
            data.image_2 = $('#juru-fauna-image-2').val();
            data.image_3 = $('#juru-fauna-image-3').val();
        }

        if (!data.title) {
            console.error('Juru Empire Database: Title is required');
            alert('Title is required.');
            return;
        }

        $.ajax({
            url: juruAjax.ajax_url,
            method: 'POST',
            data: data,
            success: function(response) {
                console.log('Juru Empire Database: Form submission response', response);
                if (response.success) {
                    alert('Successfully added ' + type + '.');
                    window.location.href = response.data.permalink;
                } else {
                    console.error('Juru Empire Database: Submission failed', response);
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Juru Empire Database: Error submitting form', status, error);
                alert('Error submitting ' + type + ': ' + error);
            }
        });
    });
});