import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';

import '../constants.dart';
import '../providers/auth.dart';
import '../providers/search_results.dart';
import '../providers/search_visibility.dart';
import '../widgets/custom_text.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  static const Map<String, String> _indexLabels = <String, String>{
    'posts': 'Posts',
    'comments': 'Comments',
    'communities': 'Communities',
  };

  int _selectedPageIndex = 0;
  final _keywordController = TextEditingController();
  String _selectedIndex = 'posts';

  @override
  void initState() {
    super.initState();
  }

  Future<void> _performSearch(BuildContext context) async {
    final query = _keywordController.text.trim();
    final resultsProvider = context.read<SearchResultsProvider>();

    if (query.isEmpty) {
      resultsProvider.clear();
      return;
    }

    final visibilityProvider = context.read<SearchVisibilityProvider>();
    final authToken = context.read<Auth>().token;

    if (visibilityProvider.token == null || visibilityProvider.token!.isExpired) {
      await visibilityProvider.refreshToken();
    }

    final visibilityToken = visibilityProvider.token;

    if (visibilityToken == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Unable to fetch search visibility token.')),
      );
      return;
    }

    await resultsProvider.search(
      query: query,
      index: _selectedIndex,
      visibilityToken: visibilityToken,
      authToken: authToken,
    );
  }

  void _selectPage(int index) {
    setState(() {
      _selectedPageIndex = index;
    });
  }

  void _selectIndex(String index) {
    if (_selectedIndex == index) {
      return;
    }

    setState(() {
      _selectedIndex = index;
    });

    if (_keywordController.text.trim().isNotEmpty) {
      unawaited(_performSearch(context));
    }
  }

  InputDecoration getInputDecoration(String hintext, VoidCallback onSubmit) {
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
      focusedErrorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      errorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      filled: true,
      prefixIcon: const Icon(
        Icons.search,
        color: kGreyLightColor,
      ),
      suffixIcon: IconButton(
        icon: const Icon(Icons.arrow_circle_right),
        onPressed: onSubmit,
      ),
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 16),
      hintText: hintext,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20.0),
            child: Column(
              children: [
                const SizedBox(height: 80),
                SizedBox(
                  height: MediaQuery.of(context).size.height * .1,
                  child: TextFormField(
                    style: const TextStyle(fontSize: 14),
                    decoration: getInputDecoration(
                      'Search communities, posts, or comments',
                      () => _performSearch(context),
                    ),
                    controller: _keywordController,
                    keyboardType: TextInputType.text,
                    onFieldSubmitted: (_) => _performSearch(context),
                  ),
                ),
                const SizedBox(height: 16),
                Align(
                  alignment: Alignment.centerLeft,
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _indexLabels.entries
                        .map(
                          (entry) => ChoiceChip(
                            label: Text(entry.value),
                            selected: _selectedIndex == entry.key,
                            onSelected: (_) => _selectIndex(entry.key),
                          ),
                        )
                        .toList(),
                  ),
                ),
                const SizedBox(height: 16),
                Consumer<SearchResultsProvider>(
                  builder: (context, provider, child) {
                    if (provider.isLoading) {
                      return const Padding(
                        padding: EdgeInsets.symmetric(vertical: 24),
                        child: CircularProgressIndicator(),
                      );
                    }

                    if (provider.errorMessage != null) {
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 24),
                        child: Text(
                          provider.errorMessage!,
                          style: const TextStyle(color: Colors.redAccent),
                        ),
                      );
                    }

                    final hits = provider.hits;

                    if (hits.isEmpty) {
                      return const CustomText(
                        text: 'Type in search bar and press enter to search',
                        colors: kGreyLightColor,
                        fontSize: 18,
                        fontWeight: FontWeight.w400,
                      );
                    }

                    return ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: hits.length,
                      separatorBuilder: (_, __) => const Divider(),
                      itemBuilder: (ctx, index) {
                        final hit = hits[index];
                        final title = hit['title'] ?? hit['name'] ?? hit['slug'] ?? 'Result';
                        final subtitle = hit['excerpt'] ?? hit['tagline'] ?? hit['body'];
                        final indexLabel = provider.response != null
                            ? _indexLabels[provider.response!.index] ?? provider.response!.index
                            : _indexLabels[_selectedIndex] ?? _selectedIndex;

                        return ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                          title: Text(
                            title.toString(),
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          subtitle: subtitle != null
                              ? Text(
                                  subtitle.toString(),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                )
                              : null,
                          trailing: Text(
                            indexLabel,
                            style: const TextStyle(fontSize: 12, color: kGreyLightColor),
                          ),
                        );
                      },
                    );
                  },
                ),
              ],
            ),
          ),
        ),
      ),
      bottomNavigationBar: BottomNavigationBar(
        onTap: _selectPage,
        items: const [
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.school_outlined),
            activeIcon: Icon(Icons.school),
            label: 'My Course',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.favorite_border),
            activeIcon: Icon(Icons.favorite),
            label: 'Wishlist',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.account_circle_outlined),
            activeIcon: Icon(Icons.account_circle),
            label: 'Account',
          ),
        ],
        backgroundColor: Colors.white,
        unselectedItemColor: kSecondaryColor,
        selectedItemColor: kSelectItemColor,
        currentIndex: _selectedPageIndex,
        type: BottomNavigationBarType.fixed,
      ),
    );
  }
}