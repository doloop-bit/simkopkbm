import { mount } from 'svelte';
import PAUDLanding from './PAUD/Landing.svelte';

const app = mount(PAUDLanding, {
    target: document.getElementById('paud-app'),
});

export default app;
