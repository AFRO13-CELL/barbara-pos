@php $inp = 'w-full rounded-lg border-stone-300 text-sm'; $p = $p ?? null; @endphp
<datalist id="brands">@foreach ($brands as $b)<option value="{{ $b }}">@endforeach</datalist>
<div class="grid md:grid-cols-2 gap-3">
    <label class="text-xs">Product name<input name="name" required maxlength="150" value="{{ old('name', $p?->name) }}" class="{{ $inp }}"></label>
    <label class="text-xs">Category
        <select name="category_id" required class="{{ $inp }}">
            <option value="">Choose...</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $p?->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
        </select></label>
    <label class="text-xs">Brand<input name="brand" list="brands" value="{{ old('brand', $p?->brand?->name) }}" class="{{ $inp }}"></label>
    <label class="text-xs">Image<input name="image" type="file" accept="image/*" class="{{ $inp }} bg-white"></label>
    <label class="text-xs md:col-span-2">Description<textarea name="description" rows="2" class="{{ $inp }}">{{ old('description', $p?->description) }}</textarea></label>
</div>
