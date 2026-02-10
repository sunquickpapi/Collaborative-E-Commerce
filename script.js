// Script for navigation bar
const bar = document.getElementById('bar');
const close = document.getElementById('close');
const nav = document.getElementById('navbar');

if (bar) {
    bar.addEventListener('click' , () => {
        nav.classList.add('active');
    })
}

if (close) {
    close.addEventListener('click' , () => {
        nav.classList.remove('active');
    })
}

console.log(localStorage.getItem('cart'));

/***BLOG PAGE - FULL&LESS READING********** */

document.addEventListener("DOMContentLoaded", function() {
    const toggleLinks = document.querySelectorAll('.blog-details .toggle-link');

    toggleLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const blogDetails = link.closest('.blog-details');
            const summary = blogDetails.querySelector('.summary');
            const fullContent = blogDetails.querySelector('.full-content');

            if (fullContent.style.display === 'none') {
                fullContent.style.display = 'block';
                summary.style.display = 'none';
                link.textContent = 'LESS READING';
            } else {
                fullContent.style.display = 'none';
                summary.style.display = 'block';
                link.textContent = 'CONTINUE READING';
            }
        });
    });
});

/*****************************ADMIN   PAGE****************************************************************** */
const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item=> {
	const li = item.parentElement;

	item.addEventListener('click', function () {
		allSideMenu.forEach(i=> {
			i.parentElement.classList.remove('active');
		})
		li.classList.add('active');
	})
});




// TOGGLE SIDEBAR
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
	sidebar.classList.toggle('hide');
})







const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton.addEventListener('click', function (e) {
	if(window.innerWidth < 576) {
		e.preventDefault();
		searchForm.classList.toggle('show');
		if(searchForm.classList.contains('show')) {
			searchButtonIcon.classList.replace('bx-search', 'bx-x');
		} else {
			searchButtonIcon.classList.replace('bx-x', 'bx-search');
		}
	}
})





if(window.innerWidth < 768) {
	sidebar.classList.add('hide');
} else if(window.innerWidth > 576) {
	searchButtonIcon.classList.replace('bx-x', 'bx-search');
	searchForm.classList.remove('show');
}


window.addEventListener('resize', function () {
	if(this.innerWidth > 576) {
		searchButtonIcon.classList.replace('bx-x', 'bx-search');
		searchForm.classList.remove('show');
	}
})



//const switchMode = document.getElementById('switch-mode');

//switchMode.addEventListener('change', function () {
//	if(this.checked) {
//		document.body.classList.add('dark');
//	} else {
//		document.body.classList.remove('dark');
//	}
//})

// Check if dark mode preference is stored in localStorage
const isDarkMode = localStorage.getItem('darkMode') === 'true';

// Function to enable dark mode
function enableDarkMode() {
    document.body.classList.add('dark');
    localStorage.setItem('darkMode', 'true');
}

// Function to disable dark mode
function disableDarkMode() {
    document.body.classList.remove('dark');
    localStorage.setItem('darkMode', 'false');
}

// Toggle dark mode based on localStorage preference
if (isDarkMode) {
    enableDarkMode();
    // Update switch mode checkbox state
    document.getElementById('switch-mode').checked = true;
} else {
    disableDarkMode();
    // Update switch mode checkbox state
    document.getElementById('switch-mode').checked = false;
}

// Event listener to toggle dark mode
const switchMode = document.getElementById('switch-mode');
switchMode.addEventListener('change', function () {
    if (this.checked) {
        enableDarkMode();
    } else {
        disableDarkMode();
    }
});

