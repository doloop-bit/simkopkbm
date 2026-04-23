<x-ui.modal wire:model="broadcastModal">
    <x-ui.header :title="__('Broadcast Pengingat Pembayaran')" :subtitle="__('Kirim pesan otomatis ke :count wali murid yang terpilih.', ['count' => count($selected_billings)])" separator />

    <div class="space-y-6">
        <x-ui.textarea 
            wire:model="wa_message" 
            :label="__('Draft Pesan Baru')" 
            :placeholder="__('Tulis pesan Anda...')" 
            rows="6"
            hint="Gunakan tag: {student_name}, {fee_name}, {month}, {amount}"
        />
        
        <div class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/50 rounded-2xl space-y-2">
            <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400 font-black text-xs uppercase tracking-widest">
                <x-ui.icon name="o-light-bulb" class="size-4" />
                {{ __('Tip Broadcast') }}
            </div>
            <p class="text-xs text-amber-600/80 leading-relaxed font-medium">
                {{ __('Pesan akan dikirimkan secara berurutan di latar belakang. Pastikan layanan Fonnte Anda aktif dan memiliki kuota yang cukup.') }}
            </p>
        </div>
    </div>

    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
        <x-ui.button :label="__('Batal')" wire:click="$set('broadcastModal', false)" />
        <x-ui.button :label="__('Kirim Sekarang')" class="btn-primary shadow-lg shadow-primary/20" wire:click="broadcast" spinner="broadcast" />
    </div>
</x-ui.modal>
