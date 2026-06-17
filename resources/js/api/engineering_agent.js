import axios from "../axios";

/*
|--------------------------------------------------------------------------
| Integrations
|--------------------------------------------------------------------------
*/

export const connectGithub = () =>
    axios.get("/engineering-agent/integrations/github/connect");

export const githubCallback = (code) =>
    axios.post("/engineering-agent/integrations/github/callback", {
        code,
    });

export const connectGitlab = () =>
    axios.get("/engineering-agent/integrations/gitlab/connect");

export const connectBitbucket = () =>
    axios.get("/engineering-agent/integrations/bitbucket/connect");

export const getIntegrations = () =>
    axios.get("/engineering-agent/integrations");

export const syncIntegration = (integrationId) =>
    axios.post(
        `/engineering-agent/integrations/${integrationId}/sync`
    );

/*
|--------------------------------------------------------------------------
| Repositories
|--------------------------------------------------------------------------
*/

export const getRepositories = (params = {}) =>
    axios.get("/engineering-agent/repositories", {
        params,
    });

export const getRepository = (repositoryId) =>
    axios.get(
        `/engineering-agent/repositories/${repositoryId}`
    );

export const syncRepository = (repositoryId) =>
    axios.post(
        `/engineering-agent/repositories/${repositoryId}/sync`
    );

/*
|--------------------------------------------------------------------------
| Developers
|--------------------------------------------------------------------------
*/

export const getDevelopers = (params = {}) =>
    axios.get("/engineering-agent/developers", {
        params,
    });

export const getDeveloper = (developerId) =>
    axios.get(
        `/engineering-agent/developers/${developerId}`
    );

export const getDeveloperMetrics = (developerId) =>
    axios.get(
        `/engineering-agent/developers/${developerId}/metrics`
    );

export const getDeveloperRepositories = (
    developerId
) =>
    axios.get(
        `/engineering-agent/developers/${developerId}/repositories`
    );

/*
|--------------------------------------------------------------------------
| Commits
|--------------------------------------------------------------------------
*/

export const getCommits = (params = {}) =>
    axios.get("/engineering-agent/commits", {
        params,
    });

export const syncCommits = (repositoryId) =>
    axios.post(
        `/engineering-agent/repositories/${repositoryId}/commits/sync`
    );

/*
|--------------------------------------------------------------------------
| Pull Requests
|--------------------------------------------------------------------------
*/

export const getPullRequests = (params = {}) =>
    axios.get("/engineering-agent/pull-requests", {
        params,
    });

export const getPullRequest = (pullRequestId) =>
    axios.get(
        `/engineering-agent/pull-requests/${pullRequestId}`
    );

export const syncPullRequests = (
    repositoryId
) =>
    axios.post(
        `/engineering-agent/repositories/${repositoryId}/pull-requests/sync`
    );

/*
|--------------------------------------------------------------------------
| Reviews
|--------------------------------------------------------------------------
*/

export const getReviews = (params = {}) =>
    axios.get("/engineering-agent/reviews", {
        params,
    });

export const syncReviews = (repositoryId) =>
    axios.post(
        `/engineering-agent/repositories/${repositoryId}/reviews/sync`
    );

/*
|--------------------------------------------------------------------------
| Tasks
|--------------------------------------------------------------------------
*/

export const getTasks = (params = {}) =>
    axios.get("/engineering-agent/tasks", {
        params,
    });

export const syncTasks = (repositoryId) =>
    axios.post(
        `/engineering-agent/repositories/${repositoryId}/tasks/sync`
    );

/*
|--------------------------------------------------------------------------
| Deployments
|--------------------------------------------------------------------------
*/

export const getDeployments = (params = {}) =>
    axios.get("/engineering-agent/deployments", {
        params,
    });

export const createDeployment = (payload) =>
    axios.post(
        "/engineering-agent/deployments",
        payload
    );

export const syncDeployments = (
    repositoryId
) =>
    axios.post(
        `/engineering-agent/repositories/${repositoryId}/deployments/sync`
    );

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

export const getDashboardOverview = () =>
    axios.get(
        "/engineering-agent/dashboard/overview"
    );

export const getLeaderboard = (params = {}) =>
    axios.get(
        "/engineering-agent/dashboard/leaderboard",
        {
            params,
        }
    );

export const getRepositoryStats = () =>
    axios.get(
        "/engineering-agent/dashboard/repositories"
    );

/*
|--------------------------------------------------------------------------
| Metrics
|--------------------------------------------------------------------------
*/

export const calculateMetrics = () =>
    axios.post(
        "/engineering-agent/metrics/calculate"
    );

export const getMetrics = (developerId) =>
    axios.get(
        `/engineering-agent/metrics/${developerId}`
    );

/*
|--------------------------------------------------------------------------
| AI Insights
|--------------------------------------------------------------------------
*/

export const generateInsight = (
    developerId
) =>
    axios.post(
        `/engineering-agent/insights/${developerId}/generate`
    );

export const getInsight = (developerId) =>
    axios.get(
        `/engineering-agent/insights/${developerId}`
    );

export const regenerateInsight = (
    developerId
) =>
    axios.post(
        `/engineering-agent/insights/${developerId}/regenerate`
    );