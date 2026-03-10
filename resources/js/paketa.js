import { mount } from 'svelte';
import PaketALanding from './PaketA/Landing.svelte';

const app = mount(PaketALanding, {
    target: document.getElementById('paketa-app'),
    props: {
        programName: window.programName || 'Paket A (Setara SD)',
        programLogo: window.programLogo || null
    }
});

export default app;
