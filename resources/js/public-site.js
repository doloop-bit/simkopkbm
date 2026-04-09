import { mount } from 'svelte';

// Import Pages
import Home from './Pages/Public/Home.svelte';
import About from './Pages/Public/About.svelte';
import Staff from './Pages/Public/Staff.svelte';
import Facilities from './Pages/Public/Facilities.svelte';
import ProgramsIndex from './Pages/Public/ProgramsIndex.svelte';
import ProgramsShow from './Pages/Public/ProgramsShow.svelte';
import NewsIndex from './Pages/Public/NewsIndex.svelte';
import NewsShow from './Pages/Public/NewsShow.svelte';
import Gallery from './Pages/Public/Gallery.svelte';
import Contact from './Pages/Public/Contact.svelte';
import Register from './Pages/Public/Register.svelte';

const pages = {
    'Home': Home,
    'About': About,
    'Staff': Staff,
    'Facilities': Facilities,
    'ProgramsIndex': ProgramsIndex,
    'ProgramsShow': ProgramsShow,
    'NewsIndex': NewsIndex,
    'NewsShow': NewsShow,
    'Gallery': Gallery,
    'Contact': Contact,
    'Register': Register
};

// Initial data passed from Blade shell
const data = window.pageData || {};
const componentName = data.page;
const component = pages[componentName];

if (component) {
    mount(component, {
        target: document.getElementById('public-app'),
        props: data.props || {}
    });
} else {
    console.error(`Component path "${componentName}" not found in public-site.js`);
}
