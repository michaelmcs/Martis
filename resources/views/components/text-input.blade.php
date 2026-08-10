@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 placeholder-slate-400 transition']) }}>
