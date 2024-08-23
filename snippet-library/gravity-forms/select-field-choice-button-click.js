/**
 * GravityRanger // Gravity Forms // Select Field Choice with Button Click
 * https://gravityranger.com/
 *
 * By default, this snippet will allow you to add a button to your page with a specific CSS class that, when clicked, 
 * will make a selection in a radio button field choice. You must map the field choices in this code using the choice values.
 */

// You will need to modify the code replacing basic, pro, and elite with your CSS classes and choice values

//choice one
document.querySelector(".basic").addEventListener("click", function() {
    selectRadioButton("basic");
});

//choice two
document.querySelector(".pro").addEventListener("click", function() {
    selectRadioButton("pro");
});

//choice three
document.querySelector(".elite").addEventListener("click", function() {
    selectRadioButton("elite");
});

function selectRadioButton(optionValue) {
    // Get all radio buttons in the form
    var radios = document.querySelectorAll("input[type='radio']");
    
    // Loop through them to find the one that matches the value
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].value == optionValue) {
            radios[i].checked = true;
            break;
        }
    }
}
