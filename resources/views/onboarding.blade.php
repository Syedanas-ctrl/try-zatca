{{-- resources/views/zatca/onboarding.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>ZATCA E-Invoicing Onboarding</h3>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($vendor->zatca_onboarded)
                            <div class="alert alert-success">
                                <p><strong>You have successfully onboarded with ZATCA e-invoicing!</strong></p>
                                <p>Onboarded on: {{ $vendor->zatca_onboarded_at->format('d M Y H:i') }}</p>
                                <p>Certificate expires on: {{ $vendor->zatca_certificate_expiry }}</p>
                            </div>
                        @else
                            <p>To comply with ZATCA (Zakat, Tax and Customs Authority) e-invoicing regulations, you need to register your business information. Please provide the following details:</p>

                            <form method="POST" action="{{ route('zatca.process-onboarding') }}">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="organization_name">Company/Organization Name (as registered with ZATCA)</label>
                                    <input type="text" class="form-control" id="organization_name" name="organization_name" value="{{ old('organization_name', $vendor ? $vendor->organization_name : '') }}" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="vat_number">VAT Registration Number (15 digits)</label>
                                    <input type="text" class="form-control" id="vat_number" name="vat_number" value="{{ old('vat_number', $vendor ? $vendor->vat_number : '') }}" pattern="[0-9]{15}" required>
                                    <small class="form-text text-muted">Enter your 15-digit VAT number without spaces or special characters</small>
                                </div>

                                <div class="form-group text-center">
                                    <button type="submit" class="btn btn-primary">Register with ZATCA</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- resources/views/zatca/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>ZATCA E-Invoicing Dashboard</h3>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        ZATCA Status
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Onboarding Status:</strong> 
                                            @if($vendor->zatca_onboarded)
                                                <span class="badge bg-success">Registered</span>
                                            @else
                                                <span class="badge bg-warning">Not Registered</span>
                                                <a href="{{ route('zatca.onboarding') }}" class="btn btn-sm btn-primary ms-2">Register Now</a>
                                            @endif
                                        </p>
                                        @if($vendor->zatca_onboarded)
                                            <p><strong>Onboarded On:</strong> {{ $vendor->zatca_onboarded_at->format('d M Y H:i') }}</p>
                                            <p><strong>Certificate Status:</strong> {{ $vendor->zatca_certificate_status ?? 'Active' }}</p>
                                            <p><strong>Certificate Expiry:</strong> {{ $vendor->zatca_certificate_expiry }}</p>
                                            <p><strong>VAT Number:</strong> {{ $vendor->vat_number }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        Recent ZATCA Activity
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            @if($vendor->zatca_onboarded)
                                                <li class="list-group-item">Onboarded on {{ $vendor->zatca_onboarded_at->format('d M Y H:i') }}</li>
                                                <!-- Add more activity logs here -->
                                            @else
                                                <li class="list-group-item">No activity yet. Complete onboarding to start.</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header bg-secondary text-white">
                                Recent Invoices
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>ZATCA Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->issue_date->format('d M Y') }}</td>
                                                <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                                                <td>{{ number_format($invoice->total_amount, 2) }}</td>
                                                <td>
                                                    @if($invoice->zatca_compliant)
                                                        <span class="badge bg-success">Compliant</span>
                                                    @else
                                                        <span class="badge bg-warning">Pending</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!$invoice->zatca_compliant && $vendor->zatca_onboarded)
                                                        <form action="{{ route('zatca.process-invoice', $invoice->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-primary">Process for ZATCA</button>
                                                        </form>
                                                    @endif
                                                    
                                                    @if($invoice->zatca_xml_path)
                                                        <a href="{{ route('zatca.download-xml', $invoice->id) }}" class="btn btn-sm btn-success">Download XML</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No invoices found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection