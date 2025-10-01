import 'dart:io';

class FeatureUnavailableException extends HttpException {
  FeatureUnavailableException(String message, {Uri? uri})
      : super(message, uri: uri);
}
