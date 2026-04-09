<script>
    import PublicLayout from '../../Layouts/PublicLayout.svelte';
    import FormInput from '../../Components/FormInput.svelte';
    import FormSelect from '../../Components/FormSelect.svelte';
    import FormTextarea from '../../Components/FormTextarea.svelte';
    import FormCombobox from '../../Components/FormCombobox.svelte';
    import FormRadioGroup from '../../Components/FormRadioGroup.svelte';
    import FormDatePicker from '../../Components/FormDatePicker.svelte';
    import Button from '../../Components/Button.svelte';
    import StepIndicator from '../../Components/StepIndicator.svelte';

    let { schoolProfile, levels, academicYears, provinces, cities = [] } = $props();

    // Form State
    let currentStep = $state(1);
    let totalSteps = 4;
    let submitted = $state(false);
    let registrationNumber = $state('');
    let loading = $state(false);
    let errors = $state({});

    // Form Data
    let formData = $state({
        name: '',
        nik: '',
        pob: '',
        dob: '',
        gender: '',
        phone: '',
        email: '',
        address: '',
        province_id: '',
        regency_id: '',
        district_id: '',
        village_id: '',
        father_name: '',
        mother_name: '',
        guardian_name: '',
        guardian_phone: '',
        nik_ayah: '',
        nik_ibu: '',
        no_kk: '',
        no_akta: '',
        birth_order: null,
        total_siblings: null,
        nisn: '',
        previous_school: '',
        preferred_level_id: '',
        academic_year_id: '',
        extra_field: '' // Honeypot
    });

    // Regional Data
    let regencies = $state([]);
    let districts = $state([]);
    let villages = $state([]);

    // Watchers for regional data
    $effect(() => {
        if (formData.province_id) {
            fetchRegencies(formData.province_id);
        } else {
            regencies = [];
            formData.regency_id = '';
        }
    });

    $effect(() => {
        if (formData.regency_id) {
            fetchDistricts(formData.regency_id);
        } else {
            districts = [];
            formData.district_id = '';
        }
    });

    $effect(() => {
        if (formData.district_id) {
            fetchVillages(formData.district_id);
        } else {
            villages = [];
            formData.village_id = '';
        }
    });

    async function fetchRegencies(provinceId) {
        const res = await fetch(`/api/regions/regencies/${provinceId}`);
        regencies = await res.json();
    }

    async function fetchDistricts(regencyId) {
        const res = await fetch(`/api/regions/districts/${regencyId}`);
        districts = await res.json();
    }

    async function fetchVillages(districtId) {
        const res = await fetch(`/api/regions/villages/${districtId}`);
        villages = await res.json();
    }

    // Step Navigation
    function nextStep() {
        errors = {};
        if (currentStep === 1) {
            if (!formData.name) {
                errors.name = 'Nama lengkap wajib diisi.';
                return;
            }
        }
        if (currentStep < totalSteps) {
            currentStep++;
            window.scrollTo({ top: 300, behavior: 'smooth' });
        }
    }

    function previousStep() {
        if (currentStep > 1) {
            currentStep--;
            window.scrollTo({ top: 300, behavior: 'smooth' });
        }
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const response = await fetch('/pendaftaran', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok) {
                registrationNumber = result.registration_number;
                submitted = true;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                errors = result.errors || { submit: result.message };
            }
        } catch (e) {
            errors.submit = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
        } finally {
            loading = false;
        }
    }

    const steps = [
        { label: 'Identitas', icon: 'user' },
        { label: 'Domisili', icon: 'map-pin' },
        { label: 'Keluarga', icon: 'users' },
        { label: 'Akademik', icon: 'academic' }
    ];

    const currentYear = new Date().getFullYear();
</script>

<PublicLayout {schoolProfile} currentRoute="Register">
    <!-- Page Header -->
    <div class="relative bg-slate-900 text-white overflow-hidden py-20 sm:py-24">
        <div class="absolute -top-24 -left-20 w-96 h-96 bg-amber-500/10 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-24 -right-20 w-96 h-96 bg-amber-500/5 blur-[120px] rounded-full"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 text-center space-y-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-[0.2em] bg-amber-500/10 text-amber-500 border border-amber-500/20">
                Penerimaan Siswa Baru
            </span>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-semibold font-heading tracking-tight leading-tight">
                Formulir <span class="text-amber-500">Registrasi</span>
            </h1>
            <p class="text-base text-slate-400 max-w-xl mx-auto font-normal leading-relaxed">
                Silakan lengkapi informasi yang dibutuhkan untuk memulai pendaftaran.
            </p>
            <div class="w-16 h-1 bg-amber-500/30 mx-auto rounded-full"></div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
        {#if submitted}
            <!-- Success State -->
            <div class="text-center py-20 animate-in zoom-in duration-700">
                <div class="w-20 h-20 bg-amber-50 dark:bg-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-8 shadow-xl shadow-amber-500/10 rotate-3 transition-transform duration-500">
                    <svg class="size-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                
                <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900 dark:text-white tracking-tight mb-4">
                    Pendaftaran Terkirim!
                </h2>
                
                <div class="max-w-sm mx-auto p-8 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 mb-10">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">ID REGISTRASI</p>
                    <div class="text-3xl font-bold text-amber-500 font-mono tracking-wider mb-4">
                        {registrationNumber}
                    </div>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed">
                        Simpan nomor ini sebagai bukti registrasi. Tim administrasi kami akan segera meninjau data Anda.
                    </p>
                </div>

                <a href="/" class="inline-flex items-center px-8 py-3.5 rounded-xl bg-amber-500 text-white font-semibold tracking-tight shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all transform hover:-translate-y-0.5">
                    Kembali ke Beranda
                </a>
            </div>
        {:else}
            <!-- Step Indicators -->
            <StepIndicator {currentStep} {totalSteps} {steps} />

            <!-- Form Container -->
            <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-xl border border-slate-100 dark:border-slate-800 relative z-0">
                <div class="p-6 sm:p-10">
                    <form onsubmit={(e) => { e.preventDefault(); if (currentStep === totalSteps) handleSubmit(); else nextStep(); }}>
                        <!-- Honeypot -->
                        <div class="hidden" aria-hidden="true">
                            <input type="text" bind:value={formData.extra_field} tabindex="-1">
                        </div>

                        {#if errors.submit}
                             <div class="mb-8 p-6 bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-400 border border-rose-100 dark:border-rose-900 rounded-2xl flex items-center gap-4">
                                <svg class="size-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <p class="font-bold uppercase tracking-tight">Gangguan Registrasi</p>
                                    <p class="text-sm font-medium">{errors.submit}</p>
                                </div>
                            </div>
                        {/if}

                        <!-- Step 1: Personal Profile -->
                        {#if currentStep === 1}
                            <div class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-700 overflow-visible">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white tracking-tight">Profil Personal Calon Siswa</h2>
                                    <p class="text-[10px] font-semibold text-slate-400 mt-1 tracking-wider">Informasi fundamental identitas diri</p>
                                </div>

                                <div class="space-y-6">
                                    <FormInput 
                                        label="Nama Lengkap (Sesuai Akta Lahir)" 
                                        name="name" 
                                        bind:value={formData.name} 
                                        error={errors.name} 
                                        placeholder="Masukkan nama lengkap Anda..."
                                        required 
                                    />

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormInput 
                                            label="Nomor Induk Kependudukan (NIK)" 
                                            name="nik" 
                                            bind:value={formData.nik} 
                                            error={errors.nik} 
                                            placeholder="16 digit NIK" 
                                            maxlength="16"
                                        />
                                        <FormRadioGroup 
                                            label="Klasifikasi Gender" 
                                            name="gender" 
                                            bind:value={formData.gender} 
                                            error={errors.gender}
                                            options={[
                                                {id: 'L', name: 'Laki-laki'},
                                                {id: 'P', name: 'Perempuan'}
                                            ]}
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormCombobox 
                                            label="Tempat Kelahiran" 
                                            name="pob" 
                                            bind:value={formData.pob} 
                                            error={errors.pob} 
                                            options={cities}
                                            placeholder="Cari Kota (atau ketik Luar Negeri...)"
                                        />
                                        <FormDatePicker 
                                            label="Tanggal Kelahiran" 
                                            name="dob" 
                                            bind:value={formData.dob} 
                                            error={errors.dob} 
                                            placeholder="Pilih Tanggal Lahir..."
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormInput 
                                            label="Nomor Kontak / WhatsApp" 
                                            name="phone" 
                                            type="tel" 
                                            bind:value={formData.phone} 
                                            error={errors.phone} 
                                            placeholder="08xxxxxxxxxx"
                                        >
                                            {#snippet icon()}
                                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {/snippet}
                                        </FormInput>
                                        <FormInput 
                                            label="Alamat Surel (Email)" 
                                            name="email" 
                                            type="email" 
                                            bind:value={formData.email} 
                                            error={errors.email} 
                                            placeholder="email@identitas.com"
                                        >
                                            {#snippet icon()}
                                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {/snippet}
                                        </FormInput>
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <!-- Step 2: Domicile -->
                        {#if currentStep === 2}
                            <div class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-700 overflow-visible">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white tracking-tight">Data Domisili & Wilayah</h2>
                                    <p class="text-[10px] font-semibold text-slate-400 mt-1 tracking-wider">Lokasi tempat tinggal calon siswa saat ini</p>
                                </div>

                                <div class="space-y-6">
                                    <FormTextarea 
                                        label="Alamat Lengkap (Jl, No Rumah, RT/RW)" 
                                        name="address" 
                                        bind:value={formData.address} 
                                        error={errors.address} 
                                        placeholder="Tuliskan alamat lengkap..." 
                                    />

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormSelect 
                                            label="Provinsi" 
                                            name="province_id" 
                                            bind:value={formData.province_id} 
                                            error={errors.province_id} 
                                            options={provinces}
                                            placeholder="Pilih Provinsi..."
                                        />
                                        <FormSelect 
                                            label="Kabupaten / Kota" 
                                            name="regency_id" 
                                            bind:value={formData.regency_id} 
                                            error={errors.regency_id} 
                                            options={regencies}
                                            placeholder="Pilih Kabupaten/Kota..."
                                            disabled={!formData.province_id}
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormSelect 
                                            label="Kecamatan" 
                                            name="district_id" 
                                            bind:value={formData.district_id} 
                                            error={errors.district_id} 
                                            options={districts}
                                            placeholder="Pilih Kecamatan..."
                                            disabled={!formData.regency_id}
                                        />
                                        <FormSelect 
                                            label="Kelurahan / Desa" 
                                            name="village_id" 
                                            bind:value={formData.village_id} 
                                            error={errors.village_id} 
                                            options={villages}
                                            placeholder="Pilih Kelurahan/Desa..."
                                            disabled={!formData.district_id}
                                        />
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <!-- Step 3: Family -->
                        {#if currentStep === 3}
                            <div class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-700 overflow-visible">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white tracking-tight leading-none">Data Orang Tua / Wali</h2>
                                    <p class="text-[10px] font-semibold text-slate-400 mt-1 tracking-wider">Informasi penanggung jawab & asal usul keluarga</p>
                                </div>

                                <div class="space-y-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormInput label="Nama Lengkap Ayah Kandung" name="father_name" bind:value={formData.father_name} error={errors.father_name} placeholder="Nama sesuai dokumen resmi" />
                                        <FormInput label="NIK Ayah" name="nik_ayah" bind:value={formData.nik_ayah} error={errors.nik_ayah} placeholder="16 digit NIK" maxlength="16" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormInput label="Nama Lengkap Ibu Kandung" name="mother_name" bind:value={formData.mother_name} error={errors.mother_name} placeholder="Nama sesuai dokumen resmi" />
                                        <FormInput label="NIK Ibu" name="nik_ibu" bind:value={formData.nik_ibu} error={errors.nik_ibu} placeholder="16 digit NIK" maxlength="16" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormInput label="Nomor Kartu Keluarga (KK)" name="no_kk" bind:value={formData.no_kk} error={errors.no_kk} placeholder="16 digit nomor KK" maxlength="16" />
                                        <FormInput label="Nomor Registrasi Akta Lahir" name="no_akta" bind:value={formData.no_akta} error={errors.no_akta} placeholder="Sesuai yang tertera di akta" />
                                    </div>

                                    <div class="p-6 bg-slate-50 dark:bg-slate-800/30 rounded-2xl border border-slate-100 dark:border-slate-700 space-y-6">
                                        <h3 class="text-[10px] font-semibold text-slate-500 tracking-wider">Opsi Penanggung Jawab Lain / Wali</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <FormInput label="Nama Lengkap Wali" name="guardian_name" bind:value={formData.guardian_name} error={errors.guardian_name} placeholder="Opsional jika ada wali" />
                                            <FormInput label="Kontak Aktif Wali" name="guardian_phone" bind:value={formData.guardian_phone} error={errors.guardian_phone} placeholder="08xxxxxxxxxx" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-100 dark:border-slate-800 pt-8">
                                        <FormInput label="Urutan Kelahiran (Anak Ke-)" name="birth_order" type="number" bind:value={formData.birth_order} error={errors.birth_order} />
                                        <FormInput label="Jumlah Saudara Kandung" name="total_siblings" type="number" bind:value={formData.total_siblings} error={errors.total_siblings} />
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <!-- Step 4: Academic -->
                        {#if currentStep === 4}
                            <div class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-700 overflow-visible">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white tracking-tight leading-none">Data Akademik & Preferensi</h2>
                                    <p class="text-[10px] font-semibold text-slate-400 mt-1 tracking-wider">Tahap akhir verifikasi informasi akademik</p>
                                </div>

                                <div class="space-y-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormInput label="Nomor Induk Siswa Nasional (NISN)" name="nisn" bind:value={formData.nisn} error={errors.nisn} placeholder="10 digit NISN" maxlength="10" />
                                        <FormInput label="Lembaga Pendidikan Sebelumnya" name="previous_school" bind:value={formData.previous_school} error={errors.previous_school} placeholder="Nama sekolah/asal instansi" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FormSelect label="Jenjang yang Diminati" name="preferred_level_id" bind:value={formData.preferred_level_id} error={errors.preferred_level_id} options={levels} />
                                        <FormSelect label="Periode Tahun Ajaran" name="academic_year_id" bind:value={formData.academic_year_id} error={errors.academic_year_id} options={academicYears} />
                                    </div>
                                </div>

                                <!-- Summary Preview -->
                                <div class="mt-10 p-6 sm:p-8 bg-slate-50 dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 relative overflow-hidden shadow-inner">
                                    <h3 class="text-[10px] font-semibold text-amber-500 tracking-wider mb-6 flex items-center gap-3">
                                        <span class="w-8 h-px bg-amber-500/20"></span>
                                        Ringkasan Konfirmasi Registrasi
                                    </h3>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-3 relative text-slate-800 dark:text-slate-200">
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                                <span class="text-[10px] font-semibold text-slate-400 font-mono tracking-wider">Nama Lengkap</span>
                                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 tracking-tight text-right">{formData.name || '-'}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                                <span class="text-[10px] font-semibold text-slate-400 font-mono tracking-wider">Identitas (NIK)</span>
                                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-mono text-right">{formData.nik || '-'}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                                <span class="text-[10px] font-semibold text-slate-400 font-mono tracking-wider">Kontak Aktif</span>
                                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-mono text-right">{formData.phone || '-'}</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                                <span class="text-[10px] font-semibold text-slate-400 font-mono tracking-wider">Jenjang</span>
                                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 tracking-tight text-right">{levels.find(l => l.id == formData.preferred_level_id)?.name || '-'}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                                <span class="text-[10px] font-semibold text-slate-400 font-mono tracking-wider">Tahun Ajaran</span>
                                                <span class="text-sm font-semibold text-amber-500 tracking-tight text-right">{academicYears.find(y => y.id == formData.academic_year_id)?.name || '-'}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800 border-dashed">
                                                <span class="text-[10px] font-semibold text-slate-400 font-mono tracking-wider">Asal Sekolah</span>
                                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 tracking-tight text-right overflow-hidden text-ellipsis whitespace-nowrap max-w-[150px]">{formData.previous_school || '-'}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <!-- Navigation Controls -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-10 pt-8 border-t border-slate-100 dark:border-slate-800">
                            {#if currentStep > 1}
                                <Button
                                    variant="ghost"
                                    onclick={previousStep}
                                    class="w-full sm:w-auto"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                    Kembali
                                </Button>
                            {:else}
                                <div class="hidden sm:block"></div>
                            {/if}

                            <Button
                                type="submit"
                                {loading}
                                class="w-full sm:w-auto {currentStep === totalSteps ? 'bg-amber-600 hover:bg-amber-700' : ''}"
                                size="lg"
                            >
                                {#if currentStep === totalSteps}
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                    Submit Pendaftaran
                                {:else}
                                    Langkah Berikutnya
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                {/if}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        {/if}
    </div>
</PublicLayout>
