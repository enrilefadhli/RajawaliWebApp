<table>
    <tr>
        <th>Product Code</th>
        <th>SKU</th>
        <th>Product Name</th>
        <th>Variant</th>
        <th>Category</th>
        <th>Status</th>
        <th>Minimum Stock</th>
        <th>Available Stock</th>
        <th>Selling Price</th>
        <th>Purchase Price</th>
    </tr>
    @foreach ($products as $product)
        <tr>
            <td>{{ $product->product_code }}</td>
            <td>{{ $product->sku }}</td>
            <td>{{ $product->product_name }}</td>
            <td>{{ $product->variant }}</td>
            <td>{{ $product->category?->category_name }}</td>
            <td>{{ $product->status }}</td>
            <td>{{ $product->minimum_stock }}</td>
            <td>{{ $product->available_stock }}</td>
            <td>{{ $product->selling_price }}</td>
            <td>{{ $product->purchase_price }}</td>
        </tr>
    @endforeach
</table>
