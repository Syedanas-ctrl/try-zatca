<?php

namespace App\Http\Controllers;

use App\Services\Zatca\CertificateBuilder;
use App\Services\Zatca\Exceptions\CertificateBuilderException;
use App\Services\Zatca\ZatcaAPI;
use App\Services\Zatca\Exceptions\ZatcaApiException;
use Illuminate\Support\Facades\Storage as StorageFacade;
use DateTime;
use App\Services\Zatca\{
    InvoiceType,
    AdditionalDocumentReference,
    TaxScheme,
    PartyTaxScheme,
    Address,
    LegalEntity,
    Delivery,
    Party,
    TaxCategory,
    TaxSubTotal,
    TaxTotal,
    LegalMonetaryTotal,
    ClassifiedTaxCategory,
    Item,
    Price,
    InvoiceLine,
    GeneratorInvoice,
    Invoice,
    UnitCode,
    Storage,
    InvoiceSigner,
    Attachment,
    PaymentMeans,
    AllowanceCharge,
    SignatureInformation,
    UBLDocumentSignatures,
    ExtensionContent,
    UBLExtension,
    UBLExtensions,
    Signature
};
use App\Services\Zatca\Helpers\Certificate;
use App\Services\Zatca\Helpers\InvoiceExtension;
class TestController extends Controller
{

    public function getCsr()
    {
        try {
            (new CertificateBuilder())
                // The Organization Identifier must be 15 digits, starting andending with 3
                ->setOrganizationIdentifier('399999999900003')
                // string $solutionName .. The solution provider name
                // string $model .. The model of the unit the stamp is being generated for
                // string $serialNumber .. # If you have multiple devices each should have a unique serial number
                ->setSerialNumber('POS', 'A1', '98765')
                ->setCommonName('مؤسسة وقت الاستجابة')          // The common name to be used in the certificate
                ->setCountryName('SA')                          // The Country name must be Two chars only
                ->setOrganizationName('مؤسسة وقت الاستجابة')    // The name of your organization
                ->setOrganizationalUnitName('IT Department')    // Organizational unit
                ->setAddress('1234 Main St, Riyadh')            // Address
                // # Four digits, each digit acting as a bool. The order is as follows: Standard Invoice, Simplified, future use, future use 
                ->setInvoiceType(1000)
                ->setProduction(false)                          // true = Production |  false = Testing
                ->setBusinessCategory('Technology')             // Your business category like food, real estate, etc
                // Generate and save the certificate and private key
                ->generateAndSave(StorageFacade::path('certificate.csr'), StorageFacade::path('private.pem'));

            echo "Certificate and private key saved.\n";
        } catch (CertificateBuilderException $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public function getCSID()
    {
        $zatcaClient = new ZatcaAPI('sandbox');

        try {
            $otp = "123123";
            $certificatePath = StorageFacade::path('certificate.csr');
            $csr = $zatcaClient->loadCSRFromFile($certificatePath);
            $complianceResult = $zatcaClient->requestComplianceCertificate($csr, $otp);

            echo "Compliance Certificate:\n" . $complianceResult->getCertificate() . "\n";
            echo "API Secret: " . $complianceResult->getSecret() . "\n";
            echo "Request ID: " . $complianceResult->getRequestId() . "\n";

            // sava file output/ZATCA_certificate_data.json
            $outputFile = StorageFacade::path('ZATCA_certificate_data.json');
            $zatcaClient->saveToJson(
                $complianceResult->getCertificate(),
                $complianceResult->getSecret(),
                $complianceResult->getRequestId(),
                $outputFile
            );

            echo "Certificate data saved to {$outputFile}\n";

        } catch (ZatcaApiException $e) {
            echo "API Error: " . $e->getMessage();
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function getHash(string $xml): string
    {
        $xmlDom = InvoiceExtension::fromString($xml);

        $xmlDom->removeByXpath('ext:UBLExtensions');
        $xmlDom->removeByXpath('cac:Signature');
        $xmlDom->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');

        // Compute hash using SHA-256
        $invoiceHashBinary = hash('sha256', $xmlDom->getElement()->C14N(false, false), true);
        return base64_encode($invoiceHashBinary);
    }

    public function getPreviousInvoiceData()
    {

        $previousInvoicePath = StorageFacade::path('previous_signed_invoice.xml');

        if (!StorageFacade::exists($previousInvoicePath)) {
            // First invoice: return empty hash and no UUID
            return [
                'hash' => $this->getHash(''),
                'uuid' => null
            ];
        }

        // Parse previous invoice to extract UUID and compute hash
        $previousXml = (new Storage)->get($previousInvoicePath);
        $previousHash = $this->getHash($previousXml);
        $dom = new \DOMDocument();
        $dom->loadXML($previousXml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        // Extract UUID from previous invoice
        $previousUuid = $xpath->query('//cbc:UUID')->item(0)->nodeValue;

        return [
            'hash' => $previousHash,
            'uuid' => $previousUuid
        ];
    }

    public function getInvoice()
    {
        $signatureInfo = (new SignatureInformation)
            ->setReferencedSignatureID("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setID('urn:oasis:names:specification:ubl:signature:1');

        $ublDocSignatures = (new UBLDocumentSignatures)
            ->setSignatureInformation($signatureInfo);

        $extensionContent = (new ExtensionContent)
            ->setUBLDocumentSignatures($ublDocSignatures);

        $ublExtension = (new UBLExtension)
            ->setExtensionURI('urn:oasis:names:specification:ubl:dsig:enveloped:xades')
            ->setExtensionContent($extensionContent);

        // Default UBL Extensions Default
        $ublExtensions = (new UBLExtensions)
            ->setUBLExtensions([$ublExtension]);

        // --- Signature Default ---
        $signature = (new Signature)
            ->setId("urn:oasis:names:specification:ubl:signature:Invoice")
            ->setSignatureMethod("urn:oasis:names:specification:ubl:dsig:enveloped:xades");

        // --- Invoice Type ---
        $invoiceType = (new InvoiceType())
            ->setInvoice('simplified') // 'standard' or 'simplified'
            ->setInvoiceType('invoice') // 'invoice', 'debit', or 'credit', 'prepayment'
            ->setIsThirdParty(false) // Third-party transaction
            ->setIsNominal(false) // Nominal transaction
            ->setIsExportInvoice(false) // Export invoice
            ->setIsSummary(false) // Summary invoice
            ->setIsSelfBilled(false); // Self-billed invoice

        // add Attachment
        $attachment = (new Attachment())
            ->setBase64Content(
                'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==',
                'base64',
                'text/plain'
            );

        // --- Additional Document References ---
        $additionalDocs = [];

        // icv = Invoice counter value
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('ICV')
            ->setUUID("10"); //Invoice counter value

        // pih = Previous Invoice Hash
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('PIH')
            ->setAttachment($attachment); // Previous Invoice Hash

        // qr = QR Code Default
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('QR');

        // --- Tax Scheme & Party Tax Schemes ---
        $taxScheme = (new TaxScheme())
            ->setId("VAT");

        // --- Legal Entity Company ---
        $legalEntityCompany = (new LegalEntity())
            ->setRegistrationName('Maximum Speed Tech Supply');

        // --- Party Tax Scheme Company ---
        $partyTaxSchemeCompany = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId('399999999900003');

        // --- Address Company ---
        $addressCompany = (new Address())
            ->setStreetName('Prince Sultan')
            ->setBuildingNumber("2322")
            ->setCitySubdivisionName('Al-Murabba')
            ->setCityName('Riyadh')
            ->setPostalZone('23333')
            ->setCountry('SA');

        // --- Supplier Company ---
        $supplierCompany = (new Party())
            ->setPartyIdentification("1010010000")
            ->setPartyIdentificationId("CRN")
            ->setLegalEntity($legalEntityCompany)
            ->setPartyTaxScheme($partyTaxSchemeCompany)
            ->setPostalAddress($addressCompany);


        // --- Legal Entity Customer ---
        $legalEntityCustomer = (new LegalEntity())
            ->setRegistrationName('Fatoora Samples');

        // --- Party Tax Scheme Customer ---
        $partyTaxSchemeCustomer = (new PartyTaxScheme())
            ->setTaxScheme($taxScheme)
            ->setCompanyId('399999999800003');

        // --- Address Customer ---
        $addressCustomer = (new Address())
            ->setStreetName('Salah Al-Din')
            ->setBuildingNumber("1111")
            ->setCitySubdivisionName('Al-Murooj')
            ->setCityName('Riyadh')
            ->setPostalZone('12222')
            ->setCountry('SA');

        // --- Supplier Customer ---
        $supplierCustomer = (new Party())
            ->setLegalEntity($legalEntityCustomer)
            ->setPartyTaxScheme($partyTaxSchemeCustomer)
            ->setPostalAddress($addressCustomer);

        // --- Payment Means ---
        $paymentMeans = (new PaymentMeans())
            ->setPaymentMeansCode("10");


        // --- array of Tax Category Discount ---
        $taxCategoryDiscount = [];

        // --- Tax Category Discount ---
        $taxCategoryDiscount[] = (new TaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        // --- Allowance Charge (for Invoice Line) ---
        $allowanceCharges = [];
        $allowanceCharges[] = (new AllowanceCharge())
            ->setChargeIndicator(false)
            ->setAllowanceChargeReason('discount')
            ->setAmount(0.00)
            ->setTaxCategory($taxCategoryDiscount);// Tax Category Discount

        // --- Tax Category ---
        $taxCategorySubTotal = (new TaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        // --- Tax Sub Total ---
        $taxSubTotal = (new TaxSubTotal())
            ->setTaxableAmount(4)
            ->setTaxAmount(0.6)
            ->setTaxCategory($taxCategorySubTotal);

        // --- Tax Total ---
        $taxTotal = (new TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount(0.6);

        // --- Legal Monetary Total ---
        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount(4)// Total amount of the invoice
            ->setTaxExclusiveAmount(4) // Total amount without tax
            ->setTaxInclusiveAmount(4.60) // Total amount with tax
            ->setPrepaidAmount(0) // Prepaid amount
            ->setPayableAmount(4.60) // Amount to be paid
            ->setAllowanceTotalAmount(0); // Total amount of allowances

        // --- Classified Tax Category ---
        $classifiedTax = (new ClassifiedTaxCategory())
            ->setPercent(15)
            ->setTaxScheme($taxScheme);

        // --- Item (Product) ---
        $productItem = (new Item())
            ->setName('Product') // Product name
            ->setClassifiedTaxCategory($classifiedTax); // Classified tax category

        // --- Allowance Charge (for Price) ---
        $allowanceChargesPrice = [];
        $allowanceChargesPrice[] = (new AllowanceCharge())
            ->setChargeIndicator(true)
            ->setAllowanceChargeReason('discount')
            ->setAmount(0.00);

        // --- Price ---
        $price = (new Price())
            ->setUnitCode(UnitCode::UNIT)
            ->setAllowanceCharges($allowanceChargesPrice)
            ->setPriceAmount(2);

        // --- Invoice Line Tax Total ---
        $lineTaxTotal = (new TaxTotal())
            ->setTaxAmount(0.60)
            ->setRoundingAmount(4.60);

        // --- Invoice Line(s) ---
        $invoiceLine = (new InvoiceLine())
            ->setUnitCode("PCE")
            ->setId(1)
            ->setItem($productItem)
            ->setLineExtensionAmount(4)
            ->setPrice($price)
            ->setTaxTotal($lineTaxTotal)
            ->setInvoicedQuantity(2);
        $invoiceLines = [$invoiceLine];


        // Invoice
        $invoice = (new Invoice())
            ->setUBLExtensions($ublExtensions)
            ->setUUID('3cf5ee18-ee25-44ea-a444-2c37ba7f28be')
            ->setId('SME00023')
            ->setIssueDate(new DateTime('2024-09-07 17:41:08'))
            ->setIssueTime(new DateTime('2024-09-07 17:41:08'))
            ->setInvoiceType($invoiceType)
            ->setNote('ABC')->setlanguageID('ar')
            ->setInvoiceCurrencyCode('SAR') // Currency code (ISO 4217)
            ->setTaxCurrencyCode('SAR') // Tax currency code (ISO 4217)
            ->setAdditionalDocumentReferences($additionalDocs) // Additional document references
            ->setAccountingSupplierParty($supplierCompany)// Supplier company
            ->setAccountingCustomerParty($supplierCustomer) // Customer company
            ->setPaymentMeans($paymentMeans)// Payment means
            ->setAllowanceCharges($allowanceCharges)// Allowance charges
            ->setTaxTotal($taxTotal)// Tax total
            ->setLegalMonetaryTotal($legalMonetaryTotal)// Legal monetary total
            ->setInvoiceLines($invoiceLines)// Invoice lines
            ->setSignature($signature);


        try {
            // Generate the XML (default currency 'SAR')
            // Save the XML to an output file
            $toXml = GeneratorInvoice::invoice($invoice);
            (new Storage)->put(StorageFacade::path('unsigned_invoice.xml'), $toXml->getXML());
        } catch (\Exception $e) {
            // Log error message and exit
            echo "An error occurred: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public function getInvoiceSigned()
    {
        // Load the unsigned invoice XML
        $xmlInvoice = (new Storage)->get(StorageFacade::path('unsigned_invoice.xml'));

        // Load the compliance certificate data from the JSON file
        $json_certificate = (new Storage)->get(StorageFacade::path('ZATCA_certificate_data.json'));

        // Decode the JSON data
        $json_data = json_decode($json_certificate, true, 512, JSON_THROW_ON_ERROR);

        // Extract certificate details
        $certificate = $json_data['certificate'];
        $secret = $json_data['secret'];

        // Load the private key
        $privateKey = (new Storage)->get(StorageFacade::path('private.pem'));
        $cleanPrivateKey = trim(str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], "", $privateKey));

        // Create a Certificate instance
        $certificate = new Certificate(
            $certificate,
            $cleanPrivateKey,
            $secret
        );

        // Sign the invoice
        $signedInvoice = InvoiceSigner::signInvoice($xmlInvoice, $certificate);
        $hash = $signedInvoice->getHash();
        $qrCode = $signedInvoice->getQRCode();
        $xml = $signedInvoice->getXML();
        // Save the signed invoice
        (new Storage)->put(StorageFacade::path('signed_invoice.xml'), $xml);
        (new Storage)->put(StorageFacade::path('hash.txt'), $hash);
        (new Storage)->put(StorageFacade::path('qr_code.txt'), $qrCode);
    }

    public function compliance()
    {
        $signedXmlInvoice = (new Storage)->get(StorageFacade::path('signed_invoice.xml'));
        $json_certificate = (new Storage)->get(StorageFacade::path('ZATCA_certificate_data.json'));
        $json_data = json_decode($json_certificate, true, 512, JSON_THROW_ON_ERROR);
        $certificate = base64_encode($json_data['certificate']);
        $secret = $json_data['secret'];
        $invoiceHash = (new Storage)->get(StorageFacade::path('hash.txt'));
        $uuid = '3cf5ee18-ee25-44ea-a444-2c37ba7f28be';
        $zatcaClient = new ZatcaAPI('sandbox');
        $complianceResult = $zatcaClient->validateInvoiceCompliance(
            $certificate,
            $secret,
            $signedXmlInvoice,
            $invoiceHash,
            $uuid
        );
        dd($complianceResult);
    }

    public function getProdCSID()
    {
        $api = new ZatcaAPI('sandbox');
        $json_certificate = (new Storage)->get(StorageFacade::path('ZATCA_certificate_data.json'));
        $json_data = json_decode($json_certificate, true, 512, JSON_THROW_ON_ERROR);
        $certificate = base64_encode($json_data['certificate']);
        $secret = $json_data['secret'];
        $complianceRequestId = $json_data['requestId'];

        $result = $api->requestProductionCertificate($certificate, $secret, $complianceRequestId);

        echo "Compliance Certificate:\n" . $result->getCertificate() . "\n";
        echo "API Secret: " . $result->getSecret() . "\n";
        echo "Request ID: " . $result->getRequestId() . "\n";

        // sava file output/ZATCA_certificate_data.json
        $outputFile = StorageFacade::path('production_certificate.json');
        $api->saveToJson(
            $result->getCertificate(),
            $result->getSecret(),
            $result->getRequestId(),
            $outputFile
        );

        echo "Certificate data saved to {$outputFile}\n";
    }

    public function renewProdCSID()
    {
        $api = new ZatcaAPI('sandbox');
        $json_certificate = (new Storage)->get(StorageFacade::path('production_certificate.json'));
        $json_data = json_decode($json_certificate, true, 512, JSON_THROW_ON_ERROR);
        $certificate = base64_encode($json_data['certificate']);
        $secret = $json_data['secret'];
        $csr = (new Storage)->get(StorageFacade::path('certificate.csr'));
        $otp = '123123';
        $result = $api->renewProductionCertificate($certificate, $secret, $csr, $otp);

        echo "Compliance Certificate:\n" . $result->getCertificate() . "\n";
        echo "API Secret: " . $result->getSecret() . "\n";
        echo "Request ID: " . $result->getRequestId() . "\n";

        // sava file output/ZATCA_certificate_data.json
        $outputFile = StorageFacade::path('production_certificate.json');
        $api->saveToJson(
            $result->getCertificate(),
            $result->getSecret(),
            $result->getRequestId(),
            $outputFile
        );

        echo "Certificate data saved to {$outputFile}\n";
    }

    public function report()
    {
        $signedXmlInvoice = (new Storage)->get(StorageFacade::path('signed_invoice.xml'));
        $json_certificate = (new Storage)->get(StorageFacade::path('production_certificate.json'));
        $json_data = json_decode($json_certificate, true, 512, JSON_THROW_ON_ERROR);
        $certificate = base64_encode($json_data['certificate']);
        $secret = $json_data['secret'];
        $invoiceHash = (new Storage)->get(StorageFacade::path('hash.txt'));
        $uuid = '3cf5ee18-ee25-44ea-a444-2c37ba7f28be';
        $zatcaClient = new ZatcaAPI('sandbox');
        $zatcaClient->setWarningHandling(true);
        $complianceResult = $zatcaClient->reportSimpleInvoice(
            $certificate,
            $secret,
            $signedXmlInvoice,
            $invoiceHash,
            $uuid
        );
        dd($complianceResult);
    }
}