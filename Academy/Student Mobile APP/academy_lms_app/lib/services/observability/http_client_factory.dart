import 'package:http/http.dart' as http;

import 'instrumented_http_client.dart';
import 'mobile_observability_client.dart';

class HttpClientFactory {
  HttpClientFactory._();

  static http.Client create({http.Client? inner}) {
    if (inner is InstrumentedHttpClient) {
      return inner;
    }

    return InstrumentedHttpClient(
      inner: inner,
      observability: MobileObservabilityClient.instance,
    );
  }
}
