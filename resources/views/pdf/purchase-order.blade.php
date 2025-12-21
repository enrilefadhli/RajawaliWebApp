<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $purchaseOrder->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .row { width: 100%; }
        .col { display: inline-block; vertical-align: top; }
        .col-6 { width: 49%; }
        .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; table-layout: fixed; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row td { font-weight: 700; }
        .signatures { margin-top: 28px; }
        .signature-box { height: 70px; border: 1px dashed #d1d5db; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Purchase Order (PO)</div>
        <div class="muted">
            PO Code: <strong>{{ $purchaseOrder->code }}</strong>
            &nbsp;|&nbsp; Date: {{ optional($purchaseOrder->created_at)->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="row">
        <div class="col col-6">
            <div class="box">
                <div><strong>Supplier</strong></div>
                <div>{{ $supplier?->supplier_name ?? '-' }}</div>
                <div class="muted">{{ $supplier?->supplier_phone ?? '-' }}</div>
                <div class="muted">{{ $supplier?->supplier_address ?? '-' }}</div>
            </div>
        </div>
        <div class="col col-6">
            <div class="box">
                <div><strong>Reference</strong></div>
                <div>PR Code: {{ $purchaseOrder->purchaseRequest?->code ?? '-' }}</div>
                <div>Requested By: {{ $purchaseOrder->purchaseRequest?->requester?->name ?? '-' }}</div>
                <div class="muted">Notes: {{ $purchaseOrder->purchaseRequest?->request_note ?? '-' }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 36px;" class="center">No</th>
                <th>Product</th>
                <th style="width: 110px;">Product Code</th>
                <th style="width: 80px;" class="center">Qty</th>
                <th style="width: 95px;" class="center">Expiry</th>
                <th style="width: 110px;" class="right">Unit Price</th>
                <th style="width: 120px;" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php($total = 0)
            @foreach ($purchaseOrder->details as $i => $detail)
                @php($subtotal = ((float) ($detail->unit_price ?? 0)) * ((int) ($detail->quantity ?? 0)))
                @php($total += $subtotal)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $detail->product?->product_name ?? '-' }}</td>
                    <td>{{ $detail->product?->product_code ?? '-' }}</td>
                    <td class="center">{{ (int) $detail->quantity }}</td>
                    <td class="center">{{ $detail->expiry_date ? $detail->expiry_date->format('d/m/Y') : '-' }}</td>
                    <td class="right">IDR {{ number_format((float) ($detail->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td class="right">IDR {{ number_format((float) $subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="5" class="right">TOTAL</td>
                <td class="right">IDR {{ number_format((float) $total, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="signatures row">
        <div class="col col-6">
            <div class="muted" style="margin-bottom: 6px;">Prepared by</div>
            <div class="signature-box"></div>
        </div>
        <div class="col col-6">
            <div class="muted" style="margin-bottom: 6px;">Supplier confirmation</div>
            <div class="signature-box"></div>
        </div>
    </div>
</body>
</html>
