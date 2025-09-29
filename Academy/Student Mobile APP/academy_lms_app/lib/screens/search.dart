import 'package:academy_lms_app/features/search/data/search_api.dart';
import 'package:academy_lms_app/features/search/providers/search_provider.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../constants.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final TextEditingController _keywordController = TextEditingController();
  SearchScope _scope = SearchScope.communities;

  @override
  void dispose() {
    _keywordController.dispose();
    super.dispose();
  }

  InputDecoration _inputDecoration(String hintText) {
    return InputDecoration(
      enabledBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      border: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      filled: true,
      prefixIcon: const Icon(Icons.search, color: kGreyLightColor),
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 16),
      hintText: hintText,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
    );
  }

  Future<void> _performSearch(BuildContext context) async {
    final provider = context.read<SearchProvider>();
    await provider.search(query: _keywordController.text, scope: _scope);
  }

  Widget _buildScopeSelector() {
    return DropdownButtonFormField<SearchScope>(
      value: _scope,
      decoration: const InputDecoration(
        labelText: 'Scope',
        border: OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(16.0))),
      ),
      items: SearchScope.values
          .where((scope) => scope != SearchScope.all)
          .map(
            (scope) => DropdownMenuItem(
              value: scope,
              child: Text(scope.name[0].toUpperCase() + scope.name.substring(1)),
            ),
          )
          .toList(growable: false),
      onChanged: (value) => setState(() => _scope = value ?? SearchScope.communities),
    );
  }

  Widget _buildResults(SearchProvider provider) {
    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (provider.error != null) {
      return Padding(
        padding: const EdgeInsets.only(top: 24.0),
        child: Text(provider.error!, style: const TextStyle(color: Colors.red)),
      );
    }

    final payload = provider.results;
    if (payload == null) {
      return const Padding(
        padding: EdgeInsets.only(top: 24.0),
        child: Text('Enter a query to begin searching.', style: TextStyle(color: kGreyLightColor)),
      );
    }

    if (payload.scope == SearchScope.all) {
      final pages = payload.allPages;
      if (pages.isEmpty) {
        return const Padding(
          padding: EdgeInsets.only(top: 24.0),
          child: Text('No results yet.', style: TextStyle(color: kGreyLightColor)),
        );
      }

      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: pages
            .map(
              (page) => _ResultSection(
                title: '${page.scope.name[0].toUpperCase()}${page.scope.name.substring(1)} (${page.total})',
                hits: page.hits,
              ),
            )
            .toList(growable: false),
      );
    }

    final page = payload.pageForScope(payload.scope);
    if (page == null || page.hits.isEmpty) {
      return const Padding(
        padding: EdgeInsets.only(top: 24.0),
        child: Text('No matches found for this query.', style: TextStyle(color: kGreyLightColor)),
      );
    }

    return _ResultSection(
      title: '${page.scope.name[0].toUpperCase()}${page.scope.name.substring(1)} (${page.total})',
      hits: page.hits,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Search'),
        backgroundColor: Colors.white,
        foregroundColor: kSecondaryColor,
        elevation: 0,
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Consumer<SearchProvider>(
            builder: (context, provider, _) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  TextFormField(
                    controller: _keywordController,
                    decoration: _inputDecoration('Search communities, posts, members...'),
                    textInputAction: TextInputAction.search,
                    onFieldSubmitted: (_) => _performSearch(context),
                  ),
                  const SizedBox(height: 16),
                  _buildScopeSelector(),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: provider.isLoading ? null : () => _performSearch(context),
                      icon: const Icon(Icons.search),
                      label: const Text('Search'),
                    ),
                  ),
                  Expanded(
                    child: SingleChildScrollView(
                      child: _buildResults(provider),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _ResultSection extends StatelessWidget {
  const _ResultSection({required this.title, required this.hits});

  final String title;
  final List hits;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 8.0),
          child: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        ),
        ...hits.map((hit) {
          final attributes = Map<String, dynamic>.from(hit.attributes);
          final title = attributes['name'] ?? attributes['title'] ?? 'Result';
          final description = attributes['description'] ?? attributes['body'] ?? attributes['headline'] ?? '';

          return Card(
            margin: const EdgeInsets.only(bottom: 12.0),
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12.0)),
            child: ListTile(
              title: Text(title.toString()),
              subtitle: description.toString().isEmpty
                  ? null
                  : Text(
                      description.toString(),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
            ),
          );
        }),
      ],
    );
  }
}
