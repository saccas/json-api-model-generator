# {json:api} Model Generator

Generate `saccas/json-api-model` PHP models and repositories on the basis of an OpenAPI specification.

## Usage

```bash
# Create an `in` directory with OpenAPI specification and generator configuration
mkdir -p in
wget -P in https://raw.githubusercontent.com/saccas/hitobito-json-api-model/main/generator_in/generator_configuration.yaml
wget -P in https://raw.githubusercontent.com/saccas/hitobito-json-api-model/main/generator_in/openapi.yaml

# Build the generator
docker build -t json-api-generator .

# Run the generator
docker run \
  -v $PWD/in:/in \
  -v $PWD/test/packages/hitobito-json-api-model:/out \
  --rm \
  json-api-generator \
    app:generate --namespace='Saccas\HitobitoApi'
```
