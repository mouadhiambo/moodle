/**
 * JavaScript for form editing RVS payment conditions.
 *
 * @module moodle-availability_rvspayment-form
 */
M.availability_rvspayment = M.availability_rvspayment || {};

/**
 * @class M.availability_rvspayment.form
 * @extends M.core_availability.plugin
 */
M.availability_rvspayment.form = Y.Object(M.core_availability.plugin);

/**
 * Default price from settings.
 * @property defaultPrice
 * @type Number
 */
M.availability_rvspayment.form.defaultPrice = 0;

/**
 * Available currencies.
 * @property currencies
 * @type Array
 */
M.availability_rvspayment.form.currencies = [];

/**
 * Whether editing a section.
 * @property isSection
 * @type Boolean
 */
M.availability_rvspayment.form.isSection = false;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Number} defaultPrice Default price from settings
 * @param {Array} currencies Available currencies
 * @param {Boolean} isSection Whether we're editing a section
 */
M.availability_rvspayment.form.initInner = function(defaultPrice, currencies, isSection) {
    this.defaultPrice = defaultPrice;
    this.currencies = currencies;
    this.isSection = isSection;
};

/**
 * Gets the form node for this condition.
 *
 * @method getNode
 * @param {Object} json Current saved data for this condition
 * @return {Y.Node} The form node
 */
M.availability_rvspayment.form.getNode = function(json) {
    // Build currency options.
    var currencyOptions = '';
    for (var i = 0; i < this.currencies.length; i++) {
        var curr = this.currencies[i];
        var selected = (json.currency === curr.code) ? ' selected' : '';
        currencyOptions += '<option value="' + curr.code + '"' + selected + '>' + curr.name + '</option>';
    }
    
    // Create HTML structure.
    var html = '<span class="col-form-label p-r-1">' + M.util.get_string('title', 'availability_rvspayment') + '</span>';
    
    html += '<span class="availability-group form-group">';
    
    // Free checkbox.
    html += '<label class="form-check form-check-inline">';
    html += '<input type="checkbox" class="form-check-input" name="isfree"';
    if (json.isfree) {
        html += ' checked';
    }
    html += '>';
    html += '<span class="form-check-label">' + M.util.get_string('label_isfree', 'availability_rvspayment') + '</span>';
    html += '</label>';
    
    html += '</span>';
    
    // Price and currency (shown when not free).
    html += '<span class="availability-group form-group rvspayment-price-group">';
    
    // Price input.
    html += '<label>';
    html += '<span class="accesshide">' + M.util.get_string('label_price', 'availability_rvspayment') + '</span>';
    html += '<input type="number" class="form-control" name="price" min="0" step="1" ';
    html += 'placeholder="' + M.util.get_string('label_price', 'availability_rvspayment') + '" ';
    html += 'title="' + M.util.get_string('label_price', 'availability_rvspayment') + '" ';
    html += 'value="' + (json.price !== undefined ? json.price : this.defaultPrice) + '" ';
    html += 'style="width: 100px; display: inline-block;">';
    html += '</label>';
    
    // Currency select.
    html += ' <label>';
    html += '<span class="accesshide">' + M.util.get_string('label_currency', 'availability_rvspayment') + '</span>';
    html += '<select class="form-control custom-select" name="currency" title="' + M.util.get_string('label_currency', 'availability_rvspayment') + '">';
    html += currencyOptions;
    html += '</select>';
    html += '</label>';
    
    html += '</span>';
    
    // Require previous sections (only shown for sections).
    if (this.isSection) {
        html += '<span class="availability-group form-group">';
        html += '<label class="form-check form-check-inline">';
        html += '<input type="checkbox" class="form-check-input" name="requireprevious"';
        if (json.requireprevious) {
            html += ' checked';
        }
        html += '>';
        html += '<span class="form-check-label">' + M.util.get_string('label_requireprevious', 'availability_rvspayment') + '</span>';
        html += '</label>';
        html += '</span>';
    }
    
    var node = Y.Node.create('<span class="d-flex flex-wrap align-items-center">' + html + '</span>');
    
    // Update visibility based on free checkbox.
    var updatePriceVisibility = function() {
        var isFree = node.one('input[name=isfree]').get('checked');
        var priceGroup = node.one('.rvspayment-price-group');
        if (priceGroup) {
            if (isFree) {
                priceGroup.setStyle('display', 'none');
            } else {
                priceGroup.setStyle('display', '');
            }
        }
    };
    
    // Initial visibility update.
    updatePriceVisibility();
    
    // Add event handlers.
    if (!M.availability_rvspayment.form.addedEvents) {
        M.availability_rvspayment.form.addedEvents = true;
        var root = Y.one('.availability-field');
        
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_rvspayment select, .availability_rvspayment input');
        
        root.delegate('keyup', function() {
            M.core_availability.form.update();
        }, '.availability_rvspayment input[name=price]');
    }
    
    // Handle free checkbox change for this specific node.
    node.one('input[name=isfree]').on('change', function() {
        updatePriceVisibility();
        M.core_availability.form.update();
    });
    
    return node;
};

/**
 * Fills the value object with data from the form.
 *
 * @method fillValue
 * @param {Object} value Object to fill with form data
 * @param {Y.Node} node The form node
 */
M.availability_rvspayment.form.fillValue = function(value, node) {
    var isFree = node.one('input[name=isfree]').get('checked');
    value.isfree = isFree;
    
    if (!isFree) {
        var priceInput = node.one('input[name=price]');
        value.price = parseFloat(priceInput.get('value')) || 0;
        
        var currencySelect = node.one('select[name=currency]');
        value.currency = currencySelect.get('value');
    } else {
        value.price = 0;
        value.currency = 'KES';
    }
    
    var requirePrevious = node.one('input[name=requireprevious]');
    if (requirePrevious) {
        value.requireprevious = requirePrevious.get('checked');
    } else {
        value.requireprevious = false;
    }
};

/**
 * Validates the form and adds any errors.
 *
 * @method fillErrors
 * @param {Array} errors Array to add error strings to
 * @param {Y.Node} node The form node
 */
M.availability_rvspayment.form.fillErrors = function(errors, node) {
    var isFree = node.one('input[name=isfree]').get('checked');
    
    if (!isFree) {
        var price = parseFloat(node.one('input[name=price]').get('value'));
        if (isNaN(price) || price <= 0) {
            errors.push('availability_rvspayment:error_setprice');
        }
    }
};
