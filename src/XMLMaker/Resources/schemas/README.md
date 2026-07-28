# SRI XSD schemas

Official SRI XSD schemas for the electronic documents, bundled so the toolkit can
validate generated XML offline (`MTZ\Toolkit\XMLMaker\Validation\XsdValidator`)
before signing and sending, catching schema issues that would otherwise return
SRI reception **error 35** (documento inválido).

## Files

| Document | Versions |
| --- | --- |
| `factura_V*.xsd` | 1.0.0, 1.1.0, 2.0.0, 2.1.0 |
| `NotaCredito_V*.xsd` | 1.0.0, 1.1.0 |
| `NotaDebito_V*.xsd` | 1.0.0 |
| `GuiaRemision_V*.xsd` | 1.0.0, 1.1.0 |
| `LiquidacionCompra_V*.xsd` | 1.0.0, 1.1.0 |
| `ComprobanteRetencion_V*.xsd` | 1.0.0, 2.0.0 |
| `xmldsig-core-schema.xsd` | W3C XML Signature core |

The default target version per document type is resolved by
`XmlDocumentType::version()` through `SchemaLocator`.

## Provenance and modifications

- The `*_V*.xsd` files are the **official SRI schemas**, unchanged except that the
  XML prolog of the 1.1-declared files (`NotaCredito_V1.1.0`, `GuiaRemision_V1.1.0`)
  was normalized from `version="1.1"` to `version="1.0"`. libxml cannot parse an
  XML 1.1 prolog and the schemas use no XML 1.1 features, so this is purely a
  loader compatibility change with no effect on validation.
- `xmldsig-core-schema.xsd` is the W3C XML Signature core schema, with its external
  DTD `DOCTYPE` removed so it loads offline. The SRI schemas import it to resolve
  the optional `ds:Signature` element; unsigned documents validate because that
  element is `minOccurs="0"`.
