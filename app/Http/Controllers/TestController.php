<?php

namespace App\Http\Controllers;

use Saleh7\Zatca\CertificateBuilder;
use Saleh7\Zatca\Exceptions\CertificateBuilderException;
use Saleh7\Zatca\ZatcaAPI;
use Saleh7\Zatca\Exceptions\ZatcaApiException;
use Illuminate\Support\Facades\Storage as StorageFacade;
use DateTime;
use Saleh7\Zatca\{
    InvoiceType, AdditionalDocumentReference, TaxScheme, PartyTaxScheme, Address, LegalEntity, Delivery, 
    Party, TaxCategory, TaxSubTotal, TaxTotal, LegalMonetaryTotal, 
    ClassifiedTaxCategory, Item, Price, InvoiceLine, GeneratorInvoice, Invoice, UnitCode, 
    Storage, InvoiceSigner, Attachment, PaymentMeans, AllowanceCharge
};
use Saleh7\Zatca\Helpers\Certificate;
use Saleh7\Zatca\Helpers\InvoiceExtension;
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
        // --- Invoice Type ---
        $invoiceType = (new InvoiceType())
            ->setInvoice('standard') // 'standard' or 'simplified'
            ->setInvoiceType('invoice') // 'invoice', 'debit', or 'credit', 'prepayment'
            ->setIsThirdParty(false) // Third-party transaction
            ->setIsNominal(false) // Nominal transaction
            ->setIsExportInvoice(false) // Export invoice
            ->setIsSummary(false) // Summary invoice
            ->setIsSelfBilled(false); // Self-billed invoice
        
        $previousData = $this->getPreviousInvoiceData();
        $base64PIH = 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==';

        //  // --- Set PIH Attachment ---
        $pihAttachment = (new Attachment())
            ->setBase64Content($base64PIH, 'base64', 'text/plain');

        // --- Supplier & Customer Information ---
        $taxScheme = (new TaxScheme())->setId("VAT");
        
        $partyTaxSchemeSupplier = (new PartyTaxScheme())->setTaxScheme($taxScheme)->setCompanyId('311111111101113');
        $partyTaxSchemeCustomer = (new PartyTaxScheme())->setTaxScheme($taxScheme);
        
        $address = (new Address())
            ->setStreetName('Prince Sultan Street')
            ->setBuildingNumber("2322")
            ->setPlotIdentification("2223")
            ->setCitySubdivisionName('Riyadh')
            ->setCityName('Riyadh')
            ->setPostalZone('23333')
            ->setCountry('SA');
        
        // --- Delivery ---
        $delivery = (new Delivery())->setActualDeliveryDate(date('Y-m-d'));
        
        // --- Additional Document References ---
        $additionalDocs = [];
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('ICV')
            ->setUUID("23"); //Invoice counter value
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('PIH')
            ->setAttachment($pihAttachment); // Previous Invoice Hash
            // ->setPreviousInvoiceHash('NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ=='); // Previous Invoice Hash
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('QR');
        
        $legalEntity = (new LegalEntity())->setRegistrationName('مؤسسة وقت الاستجابة');
        
        $supplierCompany = (new Party())
            ->setPartyIdentification("311111111111113")
            ->setPartyIdentificationId("CRN")
            ->setLegalEntity($legalEntity)
            ->setPartyTaxScheme($partyTaxSchemeSupplier)
            ->setPostalAddress($address);
        
        $supplierCustomer = (new Party())
            ->setPartyIdentification("311111111111113")
            ->setPartyIdentificationId("NAT")
            ->setLegalEntity($legalEntity)
            ->setPartyTaxScheme($partyTaxSchemeCustomer)
            ->setPostalAddress($address);
        
        // --- Invoice Items & Pricing ---
        $classifiedTax = (new ClassifiedTaxCategory())->setPercent(15)->setTaxScheme($taxScheme);
        $productItem = (new Item())->setName('Pencil')->setClassifiedTaxCategory($classifiedTax);
        $price = (new Price())->setUnitCode(UnitCode::UNIT)->setPriceAmount(2);
        
        $lineTaxTotal = (new TaxTotal())->setTaxAmount(0.60)->setRoundingAmount(4.60);
        
        $invoiceLine = (new InvoiceLine())
            ->setUnitCode("PCE")
            ->setId(1)
            ->setItem($productItem)
            ->setLineExtensionAmount(4)
            ->setPrice($price)
            ->setTaxTotal($lineTaxTotal)
            ->setInvoicedQuantity(2);
        
        $invoiceLines = [$invoiceLine];
        
        // --- Tax Totals ---
        $taxCategory = (new TaxCategory)
            ->setPercent(15)
            ->setTaxScheme($taxScheme);
        $taxSubTotal = (new TaxSubTotal)
            ->setTaxableAmount(4)
            ->setTaxAmount(0.6)
            ->setTaxCategory($taxCategory);
        $taxTotal = (new TaxTotal)
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount(0.6);
        
        // // --- Legal Monetary Total ---
        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount(4)
            ->setTaxExclusiveAmount(4)
            ->setTaxInclusiveAmount(4.60)
            ->setPrepaidAmount(0)
            ->setPayableAmount(4.60)
            ->setAllowanceTotalAmount(0);
        
        // --- Build the Invoice ---
        $invoice = (new Invoice())
            ->setUUID('3cf5ee18-ee25-44ea-a444-2c37ba7f28be')
            ->setId('SME00023')
            ->setIssueDate(new DateTime())
            ->setIssueTime(new DateTime())
            ->setInvoiceType($invoiceType)
            ->setInvoiceCurrencyCode('SAR')
            ->setTaxCurrencyCode('SAR')
            ->setDelivery($delivery)
            ->setAccountingSupplierParty($supplierCompany)
            ->setAccountingCustomerParty($supplierCustomer)
            ->setAdditionalDocumentReferences($additionalDocs)
            ->setTaxTotal($taxTotal)
            ->setLegalMonetaryTotal($legalMonetaryTotal)
            ->setInvoiceLines($invoiceLines);
            // ......
        // --- Generate XML ---
        try {
            $generatorXml = GeneratorInvoice::invoice($invoice);
            $outputXML = $generatorXml->getXML();
            
            // Save the XML to a file
            $filePath = StorageFacade::path('unsigned_invoice.xml');
            (new Storage)->put($filePath, $outputXML);
            
            echo "Invoice XML saved to: " . $filePath . "\n";
        
        } catch (\Exception $e) {
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
    }
}