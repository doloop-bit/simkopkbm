import { mount } from 'svelte';
import PAUDLanding from './PAUD/Landing.svelte';

const app = mount(PAUDLanding, {
    target: document.getElementById('paud-app'),
    props: {
        programName: window.programName || 'PAUD Ceria',
        programLogo: window.programLogo || null
    }
});

export default app;
